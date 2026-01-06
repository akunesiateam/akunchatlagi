<?php

namespace Modules\FBLead\Jobs;

use App\Models\Tenant;
use App\Models\Tenant\Contact;
use App\Models\Tenant\CustomField;
use App\Models\User;
use App\Services\FeatureService;
use Corbital\LaravelEmails\Facades\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FBLead\Models\FacebookLead;
use Modules\FBLead\Services\FacebookGraphApiService;

class ProcessFacebookLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public $tries = 3;

    public $backoff = [30, 60, 120]; // Retry delays in seconds

    protected string $leadgenId;

    protected int $tenantId;

    protected ?string $pageId;

    protected ?string $formId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $leadgenId,
        int $tenantId,
        ?string $pageId = null,
        ?string $formId = null
    ) {
        $this->leadgenId = $leadgenId;
        $this->tenantId = $tenantId;
        $this->pageId = $pageId;
        $this->formId = $formId;

        // Set queue based on tenant settings or default
        $this->onQueue('facebook-leads');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Set tenant context
            $tenant = Tenant::find($this->tenantId);
            if (! $tenant) {
                throw new \Exception("Tenant not found: {$this->tenantId}");
            }
            $tenant->makeCurrent();

            // Check if already processed
            if (FacebookLead::isProcessed($this->leadgenId, $this->tenantId)) {
                return;
            }

            // Fetch lead data
            $leadData = $this->fetchLeadData();
            if (! $leadData) {
                throw new \Exception('Failed to fetch lead data from Facebook API');
            }

            // Create contact
            $contact = $this->createContactFromLead($leadData);

            // Mark as processed
            FacebookLead::markAsProcessed(
                $this->leadgenId,
                $this->tenantId,
                $contact ? $contact->id : null,
                $leadData,
                $this->pageId,
                $this->formId
            );
        } catch (\Exception $e) {
            app_log("Facebook lead processing failed: {$e->getMessage()}", 'error', null, [
                'leadgen_id' => $this->leadgenId,
                'tenant_id' => $this->tenantId,
                'attempt' => $this->attempts(),
            ]);

            // Mark as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                FacebookLead::markAsProcessed(
                    $this->leadgenId,
                    $this->tenantId,
                    null,
                    ['error' => $e->getMessage()],
                    $this->pageId,
                    $this->formId
                );
            }

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Fetch lead data from Facebook Graph API
     */
    private function fetchLeadData(): ?array
    {
        // Get tenant settings using tenant ID since no tenant is logged in during job execution
        $appId = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_app_id', null, $this->tenantId);
        $appSecret = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_app_secret', null, $this->tenantId);
        $fbPages = get_tenant_setting_by_tenant_id('facebook-lead', 'fb_pages', null, $this->tenantId);

        if (! $appId || ! $appSecret) {
            throw new \Exception('Facebook App ID or Secret not configured');
        }

        if (! $fbPages) {
            throw new \Exception('Facebook pages not configured. Please complete Facebook integration setup in admin panel.');
        }

        // Decode pages data
        $pages = is_string($fbPages) ? json_decode($fbPages, true) : $fbPages;
        if (! is_array($pages) || empty($pages)) {
            throw new \Exception('No Facebook pages configured. Please fetch Facebook pages from admin panel.');
        }

        // Find the page access token for this lead's page
        $pageAccessToken = null;
        if ($this->pageId) {
            foreach ($pages as $page) {
                if (isset($page['id']) && $page['id'] === $this->pageId && isset($page['access_token'])) {
                    $pageAccessToken = $page['access_token'];
                    break;
                }
            }
        }

        // If no specific page found, try to use the first available page access token
        if (! $pageAccessToken) {
            foreach ($pages as $page) {
                if (isset($page['access_token'])) {
                    $pageAccessToken = $page['access_token'];
                    break;
                }
            }
        }

        if (! $pageAccessToken) {
            throw new \Exception('No valid page access token found. Please ensure Facebook pages are properly configured with access tokens.');
        }

        $facebookService = new FacebookGraphApiService($appId, $appSecret);

        return $facebookService->makeApiRequest("/{$this->leadgenId}", [
            'access_token' => $pageAccessToken,
            'fields' => 'id,created_time,field_data',
        ]);
    }

    /**
     * Create contact from lead data (similar to webhook controller logic)
     */
    private function createContactFromLead(array $leadData): ?Contact
    {
        $fieldData = $leadData['field_data'] ?? [];
        $leadSettings = [
            'fb_lead_source' => get_tenant_setting_by_tenant_id('facebook-lead', 'fb_lead_source', null, $this->tenantId),
            'fb_lead_status' => get_tenant_setting_by_tenant_id('facebook-lead', 'fb_lead_status', null, $this->tenantId),
            'fb_lead_assigned_to' => get_tenant_setting_by_tenant_id('facebook-lead', 'fb_lead_assigned_to', null, $this->tenantId),
            'fb_lead_group' => get_tenant_setting_by_tenant_id('facebook-lead', 'fb_lead_group', null, $this->tenantId),
        ];

        // Parse lead form data
        $parsedData = $this->parseLeadFormData($fieldData);

        // Check if contact already exists
        if ($this->contactExists($parsedData)) {
            app_log('Facebook lead contact already exists', 'info', null, [
                'tenant_id' => $this->tenantId,
                'email' => $parsedData['email'] ?? 'N/A',
                'phone' => $parsedData['phone'] ?? 'N/A',
            ]);

            return null;
        }

        // Check contact limit before creating new contact
        $featureLimitChecker = app(FeatureService::class);
        $limit = $featureLimitChecker->getLimit('contacts');

        // Skip limit check if unlimited (-1) or no limit set (null)
        if ($limit !== null && $limit !== -1) {
            $currentCount = Contact::where('tenant_id', $this->tenantId)->count();

            if ($currentCount >= $limit) {
                app_log('Facebook lead creation failed: Contact limit reached', 'warning', null, [
                    'tenant_id' => $this->tenantId,
                    'current_count' => $currentCount,
                    'limit' => $limit,
                    'leadgen_id' => $this->leadgenId,
                ]);

                return null;
            }
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
                'description' => $this->generateLeadNotes($leadData),
                'tenant_id' => $this->tenantId,
                'is_enabled' => true,
                'addedfrom' => 0, // System generated
                'created_at' => isset($leadData['created_time']) ?
                    \Carbon\Carbon::parse($leadData['created_time']) : now(),
            ]);

            // Assign to groups
            if (! empty($leadSettings['fb_lead_group'])) {
                $groupIds = explode(',', $leadSettings['fb_lead_group']);
                $contact->group_id = json_encode(array_map('intval', $groupIds));
                $contact->save();
            }

            // Add custom field data
            $this->addCustomFieldData($contact, $parsedData);

            // Track feature usage for contact creation
            $featureLimitChecker->trackUsage('contacts');

            // Send email to assigned user if configured
            if ($contact->assigned_id && can_send_email('tenant-new-contact-assigned', 'tenant_email_templates') && is_smtp_valid()) {
                $this->sendContactAssignedEmail($contact);
            }

            app_log('Facebook lead contact created successfully', 'info', null, [
                'tenant_id' => $this->tenantId,
                'contact_id' => $contact->id,
                'leadgen_id' => $this->leadgenId,
                'email' => $parsedData['email'] ?? 'N/A',
                'phone' => $parsedData['phone'] ?? 'N/A',
            ]);

            return $contact;
        } catch (\Exception $e) {
            app_log("Facebook lead contact creation failed: {$e->getMessage()}", 'error', null, [
                'tenant_id' => $this->tenantId,
                'leadgen_id' => $this->leadgenId,
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
                    break;
                case 'company_name':
                case 'company':
                    $parsed['company'] = $value;
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
                default:
                    $parsed['custom_'.$name] = $value;
                    break;
            }
        }

        return $parsed;
    }

    /**
     * Check if contact already exists
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
    private function generateLeadNotes(array $leadData): string
    {
        $notes = ['Facebook Lead Generated'];

        if (isset($leadData['form']['name'])) {
            $notes[] = "Form: {$leadData['form']['name']}";
        }

        if (isset($leadData['form']['page']['name'])) {
            $notes[] = "Page: {$leadData['form']['page']['name']}";
        }

        if (isset($leadData['created_time'])) {
            $submittedAt = \Carbon\Carbon::parse($leadData['created_time'])->format('Y-m-d H:i:s');
            $notes[] = "Submitted: {$submittedAt}";
        }

        if (isset($leadData['id'])) {
            $notes[] = "Lead ID: {$leadData['id']}";
        }

        return implode("\n", $notes);
    }

    /**
     * Add custom field data to contact
     */
    private function addCustomFieldData(Contact $contact, array $parsedData): void
    {
        $customFields = CustomField::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->get()
            ->keyBy('field_name');

        if ($customFields->isEmpty()) {
            return;
        }

        $customFieldValues = [];

        foreach ($parsedData as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if (strpos($key, 'custom_') === 0) {
                $fieldName = substr($key, 7);
                if (isset($customFields[$fieldName])) {
                    $customFieldValues[$fieldName] = $value;
                }
            }
        }

        if (! empty($customFieldValues)) {
            try {
                $existingCustomData = is_array($contact->custom_fields_data)
                    ? $contact->custom_fields_data
                    : [];

                $contact->custom_fields_data = array_merge($existingCustomData, $customFieldValues);
                $contact->save();
            } catch (\Exception $e) {
                app_log('Facebook lead custom field save failed', 'warning', null, [
                    'tenant_id' => $this->tenantId,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clean and format phone number
     */
    private function cleanPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (strpos($cleaned, '+') !== false) {
            $cleaned = '+'.str_replace('+', '', $cleaned);
        }

        return $cleaned;
    }

    /**
     * Send email notification to assigned user
     */
    private function sendContactAssignedEmail(Contact $contact): void
    {
        try {
            $assignee = User::select('email', 'firstname', 'lastname')->find($contact->assigned_id);

            if ($assignee && is_smtp_valid()) {
                $assignedEmail = $assignee->email;

                // Use system user ID (0) since this is automated from Facebook lead
                $content = render_email_template(
                    'tenant-new-contact-assigned',
                    [
                        'userId' => 0, // System generated
                        'contactId' => $contact->id,
                        'tenantId' => $this->tenantId,
                    ],
                    'tenant_email_templates'
                );

                $subject = get_email_subject(
                    'tenant-new-contact-assigned',
                    [
                        'userId' => 0, // System generated
                        'contactId' => $contact->id,
                        'tenantId' => $this->tenantId,
                    ],
                    'tenant_email_templates'
                );

                $result = Email::to($assignedEmail)
                    ->subject($subject)
                    ->content($content)
                    ->send();

                if ($result) {
                    app_log('Facebook lead assignment email sent successfully', 'info', null, [
                        'tenant_id' => $this->tenantId,
                        'contact_id' => $contact->id,
                        'assigned_to' => $assignedEmail,
                        'leadgen_id' => $this->leadgenId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            app_log("Facebook lead assignment email failed: {$e->getMessage()}", 'warning', null, [
                'tenant_id' => $this->tenantId,
                'contact_id' => $contact->id,
                'assigned_id' => $contact->assigned_id,
                'leadgen_id' => $this->leadgenId,
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        app_log("Facebook lead job failed permanently: {$exception->getMessage()}", 'error', null, [
            'leadgen_id' => $this->leadgenId,
            'tenant_id' => $this->tenantId,
        ]);
    }
}
