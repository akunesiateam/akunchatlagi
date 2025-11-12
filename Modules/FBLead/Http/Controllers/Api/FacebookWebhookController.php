<?php

namespace Modules\FBLead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Contact;
use App\Models\Tenant\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\FBLead\Jobs\ProcessFacebookLeadJob;
use Modules\FBLead\Models\FacebookLead;
use Modules\FBLead\Services\FacebookGraphApiService;

class FacebookWebhookController extends Controller
{
    /**
     * Current tenant ID
     */
    protected $tenantId = null;

    /**
     * Current tenant subdomain
     */
    protected $tenantSubdomain = null;

    /**
     * Handle CORS preflight requests
     *
     * @return Response
     */
    public function options(Request $request)
    {
        return response('')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Hub-Signature, X-Hub-Signature-256')
            ->header('Access-Control-Max-Age', '86400');
    }

    /**
     * Handle Facebook webhook verification and lead processing
     * Single endpoint like WhatsApp webhook - handles both GET (verification) and POST (leads)
     *
     * @return Response
     */
    public function verify(Request $request)
    {
        // Facebook Webhook Verification (GET request with query parameters)
        if (isset($_GET['hub_mode']) && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token'])) {
            // Get tenant info from request attributes (set by middleware)
            $this->tenantId = $request->attributes->get('tenant_id');
            $this->tenantSubdomain = $request->attributes->get('tenant_subdomain');

            // Retrieve verify token from settings
            $verifyToken = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_webhook_verify_token', null, $this->tenantId);

            // Verify the webhook
            if ($_GET['hub_verify_token'] == $verifyToken && $_GET['hub_mode'] == 'subscribe') {
                // Directly output the challenge with proper headers (like WhatsApp)
                header('Content-Type: text/plain');
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, X-Hub-Signature, X-Hub-Signature-256');
                echo $_GET['hub_challenge'];
                exit;
            } else {
                // Send 403 Forbidden with a clear error message
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: text/plain');
                echo 'Verification failed: Invalid token or mode';
                exit;
            }
        }

        // Process Facebook Lead Webhook (POST request with JSON payload)
        $this->processLeadWebhook($request);
    }

    /**
     * Process Facebook lead webhook payload
     */
    private function processLeadWebhook(Request $request)
    {
        try {
            // Get tenant info from request attributes (set by middleware)
            $this->tenantId = $request->attributes->get('tenant_id');
            $this->tenantSubdomain = $request->attributes->get('tenant_subdomain');

            // Check if facebook_lead module is enabled for this tenant
            $facebookLeadEnabled = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_lead_enabled', false, $this->tenantId);
            if (! $facebookLeadEnabled) {
                return response('Facebook Lead Integration not enabled', 403);
            }

            // Verify webhook signature for security
            $signature = $request->header('X-Hub-Signature-256');
            $rawPayload = $request->getContent();

            if (! $this->verifySignature($rawPayload, $signature)) {
                Log::warning('Facebook webhook signature verification failed', [
                    'tenant_id' => $this->tenantId,
                    'ip' => $request->ip(),
                ]);

                return response('Unauthorized', 401);
            }

            $payload = $request->all();

            Log::info(t('fb_webhook_received'), [
                'tenant_id' => $this->tenantId,
                'payload' => $payload,
            ]);

            // Process webhook payload
            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $this->processChange($change);
                        }
                    }
                }
            }

            return response('OK', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Hub-Signature, X-Hub-Signature-256');

        } catch (\Exception $e) {
            app_log('Facebook webhook processing error: '.$e->getMessage(), 'error');

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Process individual webhook change
     */
    private function processChange(array $change): void
    {
        if (! isset($change['field']) || $change['field'] !== 'leadgen') {
            return;
        }

        if (! isset($change['value']['leadgen_id'])) {
            return;
        }

        $leadgenId = $change['value']['leadgen_id'];
        $pageId = $change['value']['page_id'] ?? null;
        $formId = $change['value']['form_id'] ?? null;
        $adgroupId = $change['value']['adgroup_id'] ?? null;
        $adId = $change['value']['ad_id'] ?? null;

        Log::info(t('fb_processing_lead'), [
            'tenant_id' => $this->tenantId,
            'leadgen_id' => $leadgenId,
            'page_id' => $pageId,
            'form_id' => $formId,
            'adgroup_id' => $adgroupId,
            'ad_id' => $adId,
        ]);

        // Check if this lead has already been processed (duplicate prevention)
        if (FacebookLead::isProcessed($leadgenId, $this->tenantId)) {
            Log::info(t('fb_lead_already_processed'), [
                'tenant_id' => $this->tenantId,
                'leadgen_id' => $leadgenId,
            ]);

            return;
        }

        // Check if we should process async or sync
        $processAsync = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_process_async', true, $this->tenantId);

        if ($processAsync) {
            // Dispatch job for async processing
            ProcessFacebookLeadJob::dispatch(
                $leadgenId,
                $this->tenantId,
                $pageId,
                $formId
            );

            Log::info(t('fb_lead_queued_for_processing'), [
                'tenant_id' => $this->tenantId,
                'leadgen_id' => $leadgenId,
                'page_id' => $pageId,
            ]);
        } else {
            // Process synchronously
            try {
                // Fetch lead data from Facebook Graph API
                $leadData = $this->fetchLeadData($leadgenId);

                if ($leadData) {
                    // Create contact from Facebook lead data
                    $contact = $this->createContactFromLead($leadData, $pageId);

                    if ($contact) {
                        // Mark lead as processed
                        FacebookLead::markAsProcessed(
                            $leadgenId,
                            $this->tenantId,
                            $contact->id,
                            $leadData,
                            $pageId,
                            $formId
                        );

                        Log::info(t('fb_lead_processed_successfully'), [
                            'tenant_id' => $this->tenantId,
                            'leadgen_id' => $leadgenId,
                            'contact_id' => $contact->id,
                            'page_id' => $pageId,
                        ]);
                    } else {
                        // Mark as failed
                        FacebookLead::markAsProcessed(
                            $leadgenId,
                            $this->tenantId,
                            null,
                            $leadData,
                            $pageId,
                            $formId
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::error(t('fb_lead_processing_failed'), [
                    'tenant_id' => $this->tenantId,
                    'leadgen_id' => $leadgenId,
                    'page_id' => $pageId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Fetch lead data from Facebook Graph API with retry logic
     */
    private function fetchLeadData(string $leadgenId): ?array
    {
        $accessToken = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_access_token', null, $this->tenantId);
        $appId = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_app_id', null, $this->tenantId);
        $appSecret = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_app_secret', null, $this->tenantId);

        if (! $accessToken || ! $appId || ! $appSecret) {
            Log::error(t('fb_credentials_missing'), [
                'tenant_id' => $this->tenantId,
                'leadgen_id' => $leadgenId,
                'has_token' => ! empty($accessToken),
                'has_app_id' => ! empty($appId),
                'has_app_secret' => ! empty($appSecret),
            ]);

            return null;
        }

        $facebookService = new FacebookGraphApiService($appId, $appSecret);
        $maxRetries = 3;
        $retryDelay = 1; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $facebookService->makeApiRequest("/{$leadgenId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,created_time,field_data,form{id,name,locale,page{id,name}},platform',
                ]);

                if (isset($response['field_data'])) {
                    return $response;
                }

                Log::warning(t('fb_lead_data_invalid'), [
                    'tenant_id' => $this->tenantId,
                    'leadgen_id' => $leadgenId,
                    'attempt' => $attempt,
                    'response' => $response,
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay * $attempt); // Exponential backoff

                    continue;
                }

                return null;

            } catch (\Exception $e) {
                Log::error(t('fb_lead_fetch_failed'), [
                    'tenant_id' => $this->tenantId,
                    'leadgen_id' => $leadgenId,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay * $attempt);

                    continue;
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Create contact from Facebook lead data
     */
    private function createContactFromLead(array $leadData, ?string $pageId): ?Contact
    {
        $fieldData = $leadData['field_data'] ?? [];
        $leadSettings = get_tenant_setting_by_tenant_id('facebook-lead', 'settings', [], $this->tenantId);

        // Parse lead form data
        $parsedData = $this->parseLeadFormData($fieldData);

        // Check if contact already exists
        if ($this->contactExists($parsedData)) {
            Log::info(t('fb_lead_contact_already_exists'), [
                'tenant_id' => $this->tenantId,
                'email' => $parsedData['email'] ?? 'N/A',
                'phone' => $parsedData['phone'] ?? 'N/A',
            ]);

            return null;
        }

        try {
            // Create the contact
            $contact = Contact::create([
                'firstname' => $parsedData['first_name'] ?? null,
                'lastname' => $parsedData['last_name'] ?? null,
                'email' => $parsedData['email'] ?? null,
                'phone' => $parsedData['phone'] ?? null,
                'company' => $parsedData['company'] ?? null,
                'website' => $parsedData['website'] ?? null,
                'address' => $parsedData['address'] ?? null,
                'city' => $parsedData['city'] ?? null,
                'state' => $parsedData['state'] ?? null,
                'zip' => $parsedData['zip_code'] ?? null,
                'source_id' => $leadSettings['fb_lead_source'] ?? null,
                'status_id' => $leadSettings['fb_lead_status'] ?? null,
                'assigned_id' => $leadSettings['fb_lead_assigned_to'] ?? null,
                'description' => $this->generateLeadNotes($leadData, $pageId),
                'tenant_id' => $this->tenantId,
                'is_enabled' => true,
                'addedfrom' => 0, // System generated
                'created_at' => isset($leadData['created_time']) ?
                    \Carbon\Carbon::parse($leadData['created_time']) : now(),
            ]);

            // Assign to groups if specified
            if (! empty($leadSettings['fb_lead_group'])) {
                $groupIds = explode(',', $leadSettings['fb_lead_group']);
                $contact->group_id = json_encode(array_map('intval', $groupIds));
                $contact->save();
            }

            // Add custom field data
            $this->addCustomFieldData($contact, $parsedData);

            Log::info(t('fb_lead_contact_created'), [
                'tenant_id' => $this->tenantId,
                'contact_id' => $contact->id,
                'leadgen_id' => $leadData['id'] ?? null,
            ]);

            return $contact;

        } catch (\Exception $e) {
            Log::error(t('fb_lead_contact_creation_failed'), [
                'tenant_id' => $this->tenantId,
                'leadgen_id' => $leadData['id'] ?? null,
                'error' => $e->getMessage(),
                'lead_data' => $parsedData,
            ]);

            return null;
        }
    }

    /**
     * Parse Facebook lead form data into structured format
     */
    private function parseLeadFormData(array $fieldData): array
    {
        $parsed = [];

        foreach ($fieldData as $field) {
            $name = strtolower($field['name'] ?? '');
            $values = $field['values'] ?? [];

            if (empty($values)) {
                continue;
            }

            $value = is_array($values) ? implode(', ', $values) : $values;

            // Map common field names to standard contact fields
            switch ($name) {
                case 'email':
                    $parsed['email'] = $value;
                    break;

                case 'phone_number':
                case 'phone':
                    $parsed['phone'] = $this->cleanPhoneNumber($value);
                    break;

                case 'first_name':
                    $parsed['first_name'] = $value;
                    break;

                case 'last_name':
                    $parsed['last_name'] = $value;
                    break;

                case 'full_name':
                case 'name':
                    $nameParts = explode(' ', $value, 2);
                    $parsed['first_name'] = $nameParts[0] ?? '';
                    $parsed['last_name'] = $nameParts[1] ?? '';
                    $parsed['name'] = $value;
                    break;

                case 'company_name':
                case 'company':
                    $parsed['company'] = $value;
                    break;

                case 'job_title':
                case 'title':
                    $parsed['job_title'] = $value;
                    break;

                case 'website':
                    $parsed['website'] = $value;
                    break;

                case 'address':
                case 'street_address':
                    $parsed['address'] = $value;
                    break;

                case 'city':
                    $parsed['city'] = $value;
                    break;

                case 'state':
                    $parsed['state'] = $value;
                    break;

                case 'zip_code':
                case 'postal_code':
                    $parsed['zip_code'] = $value;
                    break;

                case 'country':
                    $parsed['country'] = $value;
                    break;

                default:
                    // Store custom fields with original field name
                    $parsed['custom_'.$name] = $value;
                    break;
            }
        }

        return $parsed;
    }

    /**
     * Check if contact already exists based on email or phone
     */
    private function contactExists(array $parsedData): bool
    {
        $exists = false;

        if (! empty($parsedData['email'])) {
            $exists = Contact::where('tenant_id', $this->tenantId)
                ->where('email', $parsedData['email'])
                ->exists();
        }

        if (! $exists && ! empty($parsedData['phone'])) {
            $exists = Contact::where('tenant_id', $this->tenantId)
                ->where('phone', $parsedData['phone'])
                ->exists();
        }

        return $exists;
    }

    /**
     * Generate notes for the contact from lead data
     */
    private function generateLeadNotes(array $leadData, ?string $pageId): string
    {
        $notes = [t('fb_lead_source_note')];

        if (isset($leadData['form']['name'])) {
            $notes[] = t('fb_lead_form_name', ['name' => $leadData['form']['name']]);
        }

        if (isset($leadData['form']['page']['name'])) {
            $notes[] = t('fb_lead_page_name', ['name' => $leadData['form']['page']['name']]);
        }

        if (isset($leadData['created_time'])) {
            $submittedAt = \Carbon\Carbon::parse($leadData['created_time'])->format('Y-m-d H:i:s');
            $notes[] = t('fb_lead_submitted_at', ['time' => $submittedAt]);
        }

        if (isset($leadData['id'])) {
            $notes[] = t('fb_lead_id_note', ['id' => $leadData['id']]);
        }

        return implode("\n", $notes);
    }

    /**
     * Add custom field data to contact with improved mapping logic
     */
    private function addCustomFieldData(Contact $contact, array $parsedData): void
    {
        $customFields = CustomField::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->get()
            ->keyBy('field_name');

        if ($customFields->isEmpty()) {
            Log::debug('No custom fields available for tenant', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        $customFieldValues = [];

        foreach ($parsedData as $key => $value) {
            if (empty($value)) {
                continue; // Skip empty values
            }

            // Handle custom fields (those starting with 'custom_')
            if (strpos($key, 'custom_') === 0) {
                $fieldName = substr($key, 7); // Remove 'custom_' prefix

                if (isset($customFields[$fieldName])) {
                    $customFieldValues[$fieldName] = $value;
                }
            }
            // Handle standard fields that might have custom field mappings
            elseif (isset($customFields[$key])) {
                $customFieldValues[$key] = $value;
            }
            // Try fuzzy matching for field names (lowercase, underscore variations)
            else {
                $normalizedKey = strtolower(str_replace([' ', '-'], '_', $key));

                foreach ($customFields as $fieldName => $customField) {
                    $normalizedFieldName = strtolower(str_replace([' ', '-'], '_', $fieldName));

                    if ($normalizedKey === $normalizedFieldName) {
                        $customFieldValues[$fieldName] = $value;
                        break;
                    }
                }
            }
        }

        // Set all custom field values at once
        if (! empty($customFieldValues)) {
            try {
                // Prepare data for custom_fields_data JSON column
                $existingCustomData = is_array($contact->custom_fields_data)
                    ? $contact->custom_fields_data
                    : [];

                $mergedCustomData = array_merge($existingCustomData, $customFieldValues);

                $contact->custom_fields_data = $mergedCustomData;
                $contact->save();

                Log::info('Facebook lead custom fields saved', [
                    'tenant_id' => $this->tenantId,
                    'contact_id' => $contact->id,
                    'fields_count' => count($customFieldValues),
                    'field_names' => array_keys($customFieldValues),
                ]);
            } catch (\Exception $e) {
                Log::warning(t('fb_lead_custom_field_failed'), [
                    'tenant_id' => $this->tenantId,
                    'contact_id' => $contact->id,
                    'custom_field_values' => $customFieldValues,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::debug('No matching custom fields found', [
                'tenant_id' => $this->tenantId,
                'parsed_keys' => array_keys($parsedData),
                'available_fields' => $customFields->pluck('field_name')->toArray(),
            ]);
        }
    }

    /**
     * Clean and format phone number
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except + at the beginning
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Ensure + is only at the beginning
        if (strpos($cleaned, '+') !== false) {
            $cleaned = '+'.str_replace('+', '', $cleaned);
        }

        return $cleaned;
    }

    /**
     * Verify Facebook webhook signature
     */
    private function verifySignature(string $payload, string $signature): bool
    {
        $appSecret = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_app_secret', null, $this->tenantId);

        if (! $appSecret) {
            Log::warning('Facebook webhook app secret not configured', [
                'tenant_id' => $this->tenantId,
            ]);

            return false;
        }

        // Decode JSON value if it's stored as JSON string
        if (is_string($appSecret) && str_starts_with($appSecret, '"') && str_ends_with($appSecret, '"')) {
            $appSecret = json_decode($appSecret);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);
        $receivedSignature = str_replace('sha256=', '', $signature);
        $isValid = hash_equals($expectedSignature, $receivedSignature);

        if (! $isValid) {
            Log::warning('Facebook webhook signature mismatch', [
                'tenant_id' => $this->tenantId,
                'expected_signature' => $expectedSignature,
                'received_signature' => $receivedSignature,
                'app_secret' => $appSecret,
                'payload_length' => strlen($payload),
                'payload_first_100' => substr($payload, 0, 100),
                'expected_length' => strlen($expectedSignature),
                'received_length' => strlen($receivedSignature),
            ]);
        }

        return $isValid;
    }
}
