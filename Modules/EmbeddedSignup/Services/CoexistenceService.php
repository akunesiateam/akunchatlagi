<?php

namespace Modules\EmbeddedSignup\Services;

use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Traits\WhatsApp;
use Corbital\ModuleManager\Facades\ModuleEvents;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\EmbeddedSignup\Models\CoexistenceSyncStatus;

class CoexistenceService
{
    use WhatsApp;

    protected $facebookApi;

    public function __construct(FacebookApiService $facebookApi)
    {
        $this->facebookApi = $facebookApi;
    }

    /**
     * Process coexistence embedded signup completion and sync to core models
     */
    public function processCoexistenceSignup(array $signupData, $tenantId = null): array
    {
        try {
            // COMPREHENSIVE COEXISTENCE DEBUG START
            Log::info('=== COEXISTENCE SERVICE DEBUG START ===', [
                'timestamp' => now()->toISOString(),
                'signup_data_structure' => $signupData,
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
            ]);

            $tenant_id = $tenantId ?: tenant_id();

            // Enhanced debugging for tenant context
            Log::info('COEXISTENCE: Tenant Context Detection', [
                'tenant_id_parameter' => $tenantId,
                'tenant_id_function' => tenant_id(),
                'final_tenant_id' => $tenant_id,
                'current_tenant_function' => current_tenant() ? current_tenant()->id : null,
                'tenant_subdomain_function' => tenant_subdomain(),
                'request_route_params' => request()->route() ? request()->route()->parameters() : null,
            ]);

            // Try alternative methods to get tenant context if tenant_id() returns null
            if (! $tenant_id) {
                // Try to get current tenant from session/context
                $currentTenant = current_tenant();
                if ($currentTenant && isset($currentTenant->id)) {
                    $tenant_id = $currentTenant->id;
                    Log::info('CoexistenceService: Retrieved tenant_id from current_tenant()', [
                        'tenant_id' => $tenant_id,
                    ]);
                }
            }

            // Try to get from subdomain in URL
            if (! $tenant_id) {
                $currentSubdomain = tenant_subdomain();
                if ($currentSubdomain) {
                    // We have subdomain but need to find a way to get tenant_id
                    Log::info('CoexistenceService: Found subdomain but need tenant_id', [
                        'subdomain' => $currentSubdomain,
                    ]);
                }
            }

            // Add debugging for tenant context
            if (! $tenant_id) {
                Log::error('CoexistenceService: tenant_id is null', [
                    'signup_data_keys' => array_keys($signupData),
                    'request_url' => request()->url(),
                    'route_parameters' => request()->route() ? request()->route()->parameters() : 'no route',
                    'current_tenant' => current_tenant(),
                    'tenant_subdomain' => tenant_subdomain(),
                ]);

                return [
                    'success' => false,
                    'error_code' => 'MISSING_TENANT_CONTEXT',
                    'message' => 'Tenant context not available. Please ensure you are accessing from a valid tenant URL.',
                    'suggested_actions' => [
                        'Ensure you are accessing the signup from /{subdomain}/embedded-signup/embsignin',
                        'Check that the tenant subdomain is valid',
                        'Contact technical support if the issue persists',
                    ],
                ];
            }

            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

            // Extract session data from embedded signup
            $phoneNumberId = $signupData['phoneNumberId'] ?? null;
            $waBaId = $signupData['waBaId'] ?? null;
            $code = $signupData['authResponse']['code'] ?? null;
            $businessId = $signupData['businessId'] ?? null;

            // Get Facebook app credentials
            $appId = get_setting('whatsapp.wm_fb_app_id');
            $appSecret = get_setting('whatsapp.wm_fb_app_secret');

            if (! $appId || ! $appSecret) {
                return [
                    'success' => false,
                    'error_code' => 'MISSING_FACEBOOK_CREDENTIALS',
                    'message' => 'Facebook app credentials not configured in admin settings',
                    'suggested_actions' => [
                        'Contact administrator to configure Facebook app credentials',
                        'Verify Facebook app ID and secret are set in admin settings',
                    ],
                ];
            }

            // Create or update sync status record (simplified - only track sync progress)
            $syncStatus = CoexistenceSyncStatus::updateOrCreate(
                [
                    'tenant_id' => $tenant_id,
                    'phone_number_id' => $phoneNumberId,
                ],
                [
                    'waba_id' => $waBaId,
                    'contacts_sync_status' => 'pending',
                    'history_sync_status' => 'pending',
                    'contacts_sync_started_at' => now(),
                    'history_sync_started_at' => now(),
                ]
            );

            // Exchange authorization code for access token and complete WhatsApp setup
            $setupSuccess = $this->completeWhatsAppSetup($tenant_id, $code);

            if (! $setupSuccess) {
                Log::error('Coexistence: Failed to complete WhatsApp setup', [
                    'tenant_id' => $tenant_id,
                ]);

                return [
                    'success' => false,
                    'error_code' => 'WHATSAPP_SETUP_FAILED',
                    'message' => 'Failed to complete WhatsApp connection',
                ];
            }

            // Start the sync process
            $this->initiateCoexistenceSync($tenant_id, $subdomain, $phoneNumberId, $waBaId);

            ModuleEvents::trigger('coexistence_signup_completed', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $waBaId,
                'sync_status_id' => $syncStatus->id,
            ]);

            return [
                'success' => true,
                'message' => 'Coexistence signup completed successfully',
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $waBaId,
                'sync_started' => true,
            ];

        } catch (Exception $e) {
            Log::error('Coexistence signup failed', [
                'error' => $e->getMessage(),
                'signup_data' => $signupData,
                'tenant_id' => tenant_id(),
            ]);

            return [
                'success' => false,
                'error_code' => 'COEXISTENCE_SIGNUP_FAILED',
                'message' => 'Failed to process coexistence signup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Start coexistence sync process
     */
    protected function initiateCoexistenceSync(string $tenant_id, string $subdomain, string $phoneNumberId, string $waBaId): void
    {
        Log::info('Coexistence sync initiated', [
            'tenant_id' => $tenant_id,
            'subdomain' => $subdomain,
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $waBaId,
        ]);

        try {
            // Get WhatsApp access token for this tenant
            $accessToken = get_tenant_setting_by_tenant_id('whatsapp', 'wm_access_token', null, $tenant_id);

            if (! $accessToken) {
                Log::error('No access token found for coexistence sync', ['tenant_id' => $tenant_id]);

                return;
            }

            // Sync contacts from WhatsApp Business
            $this->syncWhatsAppContacts($tenant_id, $subdomain, $phoneNumberId, $accessToken);

            // Sync message history from WhatsApp Business
            $this->syncWhatsAppMessages($tenant_id, $subdomain, $phoneNumberId, $accessToken);

        } catch (Exception $e) {
            Log::error('Coexistence sync failed', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync WhatsApp Business contacts via Graph API
     */
    protected function syncWhatsAppContacts(string $tenant_id, string $subdomain, string $phoneNumberId, string $accessToken): void
    {
        try {
            // Get WABA ID from tenant settings
            $wabaId = get_tenant_setting_by_tenant_id('whatsapp', 'wm_business_account_id', null, $tenant_id);

            if (empty($wabaId)) {
                Log::warning('WABA ID not found for tenant', [
                    'tenant_id' => $tenant_id,
                    'phone_number_id' => $phoneNumberId,
                ]);

                return;
            }

            // Get real WhatsApp Business contacts through conversations
            $realContacts = $this->fetchWhatsAppBusinessContacts($phoneNumberId, $wabaId, $accessToken);

            Log::info('Fetched WhatsApp Business contacts', [
                'tenant_id' => $tenant_id,
                'contact_count' => count($realContacts),
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $wabaId,
            ]);

            $synced = 0;
            foreach ($realContacts as $contactData) {
                try {
                    $phoneNumber = $this->normalizePhoneNumber($contactData['phone_number'] ?? '');

                    if (empty($phoneNumber)) {
                        continue;
                    }

                    // Check if contact already exists
                    $contact = Contact::fromTenant($subdomain)
                        ->where('phone', $phoneNumber)
                        ->first();

                    if (! $contact) {
                        // Extract names from WhatsApp contact data
                        $firstName = $contactData['first_name'] ?? '';
                        $lastName = $contactData['last_name'] ?? '';
                        $profileName = $contactData['profile_name'] ?? '';

                        // If no first/last name, try to parse from profile name
                        if (empty($firstName) && empty($lastName) && ! empty($profileName)) {
                            $nameParts = explode(' ', trim($profileName), 2);
                            $firstName = $nameParts[0] ?? '';
                            $lastName = $nameParts[1] ?? '';
                        }

                        // Fallback to phone number if no name available
                        if (empty($firstName)) {
                            $firstName = 'WhatsApp';
                        }
                        if (empty($lastName)) {
                            $lastName = 'Contact';
                        }

                        // Create new contact with WhatsApp Business data
                        $contact = Contact::fromTenant($subdomain)->create([
                            'tenant_id' => $tenant_id,
                            'firstname' => $firstName,
                            'lastname' => $lastName,
                            'phone' => $phoneNumber,
                            'type' => 'lead',
                            'source_id' => 1, // Use default source for now
                            'status_id' => 1, // Use default status for now
                            'addedfrom' => 0,
                            'description' => 'Synced from WhatsApp Business via Coexistence - Source: '.($contactData['source'] ?? 'unknown'),
                            'is_enabled' => true,
                        ]);

                        $synced++;

                        Log::info('Real WhatsApp Business contact synced', [
                            'contact_id' => $contact->id,
                            'phone' => $phoneNumber,
                            'name' => $firstName.' '.$lastName,
                            'source' => $contactData['source'] ?? 'unknown',
                            'tenant_id' => $tenant_id,
                        ]);
                    } else {
                        Log::info('WhatsApp contact already exists, skipping', [
                            'existing_contact_id' => $contact->id,
                            'phone' => $phoneNumber,
                            'tenant_id' => $tenant_id,
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error('Individual WhatsApp contact sync failed', [
                        'error' => $e->getMessage(),
                        'contact_data' => $contactData,
                    ]);
                }
            }

            // Update sync status
            CoexistenceSyncStatus::where('tenant_id', $tenant_id)
                ->where('phone_number_id', $phoneNumberId)
                ->update([
                    'contacts_sync_status' => 'completed',
                    'total_contacts_synced' => $synced,
                    'contacts_sync_completed_at' => now(),
                ]);

            Log::info('Contacts sync completed', [
                'tenant_id' => $tenant_id,
                'contacts_synced' => $synced,
            ]);

        } catch (Exception $e) {
            Log::error('Contacts sync failed', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage(),
            ]);

            CoexistenceSyncStatus::where('tenant_id', $tenant_id)
                ->where('phone_number_id', $phoneNumberId)
                ->update([
                    'contacts_sync_status' => 'failed',
                    'contacts_sync_completed_at' => now(),
                ]);
        }
    }

    /**
     * Fetch real WhatsApp Business contacts using Meta's Coexistence API
     * Uses the official smb_app_data endpoint for WhatsApp Business App coexistence
     */
    protected function fetchWhatsAppBusinessContacts(string $phoneNumberId, string $wabaId, string $accessToken): array
    {
        try {
            // Step 1: Initiate contacts synchronization using Meta's official Coexistence API
            Log::info('Initiating WhatsApp Business App contacts sync', [
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $wabaId,
                'method' => 'POST /smb_app_data with sync_type=smb_app_state_sync',
            ]);

            $apiVersion = 'v21.0'; // Use latest stable API version
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/smb_app_data";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, [
                'messaging_product' => 'whatsapp',
                'sync_type' => 'smb_app_state_sync',
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $requestId = $responseData['request_id'] ?? 'unknown';

                Log::info('WhatsApp Business App contacts sync initiated successfully', [
                    'phone_number_id' => $phoneNumberId,
                    'request_id' => $requestId,
                    'response' => $responseData,
                    'note' => 'Contacts will be delivered via smb_app_state_sync webhooks',
                ]);

                // Return empty array as contacts will come via webhooks
                // The actual contacts will be processed when smb_app_state_sync webhooks are received
                return [];

            } else {
                $errorData = $response->json();
                Log::error('Failed to initiate WhatsApp Business App contacts sync', [
                    'phone_number_id' => $phoneNumberId,
                    'status_code' => $response->status(),
                    'error_response' => $errorData,
                    'url' => $url,
                ]);

                // Return empty array on API failure
                return [];
            }

        } catch (Exception $e) {
            Log::error('Exception during WhatsApp Business App contacts sync', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $wabaId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get contacts from business profile info
     */
    protected function getContactsFromBusinessProfile(string $wabaId, string $accessToken): array
    {
        try {
            // Business profile doesn't contain contact info, but this is a placeholder
            // for future enhancements where we might get profile or other data
            return [];
        } catch (Exception $e) {
            Log::error('Error fetching business profile contacts', [
                'error' => $e->getMessage(),
                'waba_id' => $wabaId,
            ]);

            return [];
        }
    }

    /**
     * Get contacts from WhatsApp Business conversations
     * Note: WhatsApp Business API doesn't provide direct access to historical conversations/contacts
     * This method now focuses on webhook-based contact discovery
     */
    protected function getContactsFromConversations(string $wabaId, string $accessToken): array
    {
        try {
            Log::info('Conversations endpoint not available in WhatsApp Business API', [
                'note' => 'WhatsApp Business API does not provide direct access to historical conversations',
                'alternative' => 'Contacts will be discovered through incoming webhooks',
                'waba_id' => $wabaId,
            ]);

            // WhatsApp Business API does not provide direct access to conversations/contacts
            // Contacts are typically discovered through:
            // 1. Incoming webhook messages
            // 2. Business-initiated conversations
            // 3. Manual contact addition

            return [];

        } catch (Exception $e) {
            Log::error('Error in conversations check', [
                'error' => $e->getMessage(),
                'waba_id' => $wabaId,
            ]);

            return [];
        }
    }

    /**
     * Get contacts from recent WhatsApp messages
     */
    protected function getContactsFromRecentMessages(string $phoneNumberId, string $accessToken): array
    {
        try {
            // Note: WhatsApp Business API doesn't provide message history retrieval
            // But we can check for recent webhook data or cached message info
            // This is a placeholder for when you implement webhook message storage

            Log::info('Recent message contacts sync skipped', [
                'reason' => 'WhatsApp API does not provide historical message access',
                'phone_number_id' => $phoneNumberId,
            ]);

            return [];

        } catch (Exception $e) {
            Log::error('Error fetching contacts from recent messages', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumberId,
            ]);

            return [];
        }
    }

    /**
     * Get detailed contact information for a phone number
     */
    protected function getContactInfoFromPhone(string $phoneNumber, string $accessToken): ?array
    {
        try {
            // WhatsApp Business API doesn't provide a direct contact lookup
            // But you can check if a number is a WhatsApp user
            // This would typically be done through webhook events when users message you

            // For now, return basic structure
            return [
                'phone_number' => $phoneNumber,
                'first_name' => '',
                'last_name' => '',
                'profile_name' => '',
                'source' => 'phone_lookup',
            ];

        } catch (Exception $e) {
            Log::error('Error getting contact info from phone', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
            ]);

            return null;
        }
    }

    /**
     * Sync WhatsApp message history using Meta's Coexistence API
     * Uses the official smb_app_data endpoint for WhatsApp Business App message history
     */
    protected function syncWhatsAppMessages(string $tenant_id, string $subdomain, string $phoneNumberId, string $accessToken): void
    {
        try {
            // Step 2: Initiate message history synchronization using Meta's official Coexistence API
            Log::info('Initiating WhatsApp Business App message history sync', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'method' => 'POST /smb_app_data with sync_type=history',
            ]);

            $apiVersion = 'v21.0'; // Use latest stable API version
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/smb_app_data";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, [
                'messaging_product' => 'whatsapp',
                'sync_type' => 'history',
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $requestId = $responseData['request_id'] ?? 'unknown';

                Log::info('WhatsApp Business App message history sync initiated successfully', [
                    'tenant_id' => $tenant_id,
                    'phone_number_id' => $phoneNumberId,
                    'request_id' => $requestId,
                    'response' => $responseData,
                    'note' => 'Message history will be delivered via history webhooks (up to 180 days)',
                ]);

                // Update sync status to indicate API call was successful
                CoexistenceSyncStatus::where('tenant_id', $tenant_id)
                    ->where('phone_number_id', $phoneNumberId)
                    ->update([
                        'history_sync_status' => 'in_progress',
                        'history_sync_request_id' => $requestId,
                        'history_sync_started_at' => now(),
                        'history_sync_completed_at' => null, // Will be updated when webhooks complete
                    ]);

            } else {
                $errorData = $response->json();
                Log::error('Failed to initiate WhatsApp Business App message history sync', [
                    'tenant_id' => $tenant_id,
                    'phone_number_id' => $phoneNumberId,
                    'status_code' => $response->status(),
                    'error_response' => $errorData,
                    'url' => $url,
                ]);

                // Update sync status to failed
                CoexistenceSyncStatus::where('tenant_id', $tenant_id)
                    ->where('phone_number_id', $phoneNumberId)
                    ->update([
                        'history_sync_status' => 'failed',
                        'history_sync_completed_at' => now(),
                    ]);
            }

        } catch (Exception $e) {
            Log::error('Exception during WhatsApp Business App message history sync', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync status to failed
            CoexistenceSyncStatus::where('tenant_id', $tenant_id)
                ->where('phone_number_id', $phoneNumberId)
                ->update([
                    'history_sync_status' => 'failed',
                    'history_sync_completed_at' => now(),
                ]);
        }
    }

    /**
     * Sync WhatsApp Business contacts into tenant's core Contact model
     */
    public function syncContacts(string $tenant_id, string $subdomain, string $phoneNumberId, string $waBaId, array $contactsData): void
    {
        try {
            $synced = 0;
            $errors = 0;

            foreach ($contactsData as $contactData) {
                try {
                    $phoneNumber = $this->normalizePhoneNumber($contactData['phone_number'] ?? '');

                    if (empty($phoneNumber)) {
                        continue;
                    }

                    // Check if contact already exists
                    $contact = Contact::fromTenant($subdomain)
                        ->where('phone', $phoneNumber)
                        ->first();

                    $fullName = $contactData['full_name'] ?? '';
                    $firstName = $contactData['first_name'] ?? '';
                    $lastName = $contactData['last_name'] ?? '';

                    // Parse name if full_name provided but first/last not provided
                    if ($fullName && (! $firstName || ! $lastName)) {
                        $nameParts = explode(' ', trim($fullName), 2);
                        $firstName = $firstName ?: $nameParts[0] ?? '';
                        $lastName = $lastName ?: $nameParts[1] ?? '';
                    }

                    if (! $contact) {
                        // Create new contact with WhatsApp Business App as source
                        $contact = Contact::fromTenant($subdomain)->create([
                            'tenant_id' => $tenant_id,
                            'firstname' => $firstName ?: 'WhatsApp',
                            'lastname' => $lastName ?: 'Business Contact',
                            'phone' => $phoneNumber,
                            'type' => 'lead', // Default to lead, can be updated later
                            'source_id' => $this->getOrCreateCoexistenceSource($tenant_id, $subdomain),
                            'status_id' => $this->getDefaultStatusId($tenant_id, $subdomain),
                            'addedfrom' => 0, // System generated
                            'description' => 'Synced from WhatsApp Business App via Coexistence',
                            'is_enabled' => true,
                        ]);

                        $synced++;
                    } else {
                        // Update existing contact if we have better information
                        $updated = false;

                        if ($firstName && empty($contact->firstname)) {
                            $contact->firstname = $firstName;
                            $updated = true;
                        }

                        if ($lastName && empty($contact->lastname)) {
                            $contact->lastname = $lastName;
                            $updated = true;
                        }

                        if ($updated) {
                            $contact->save();
                            $synced++;
                        }
                    }

                } catch (Exception $e) {
                    Log::error('Failed to sync individual contact', [
                        'contact_data' => $contactData,
                        'error' => $e->getMessage(),
                        'tenant_id' => $tenant_id,
                    ]);
                    $errors++;
                }
            }

            // Update sync status
            $this->updateSyncStatus($tenant_id, $phoneNumberId, [
                'contacts_sync_status' => 'completed',
                'contacts_sync_completed_at' => now(),
                'total_contacts_synced' => $synced,
                'last_contact_sync_at' => now(),
            ]);

            ModuleEvents::trigger('coexistence_contacts_synced', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'synced_count' => $synced,
                'errors_count' => $errors,
            ]);

        } catch (Exception $e) {
            Log::error('Contacts sync failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
            ]);

            $this->updateSyncStatus($tenant_id, $phoneNumberId, [
                'contacts_sync_status' => 'failed',
                'last_sync_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync WhatsApp Business message history into tenant's core Chat/ChatMessage models
     */
    public function syncMessageHistory(string $tenant_id, string $subdomain, string $phoneNumberId, string $waBaId, array $messagesData): void
    {
        try {
            $synced = 0;
            $errors = 0;

            foreach ($messagesData as $messageData) {
                try {
                    $fromPhone = $this->normalizePhoneNumber($messageData['from'] ?? '');
                    $toPhone = $this->normalizePhoneNumber($messageData['to'] ?? '');
                    $waMessageId = $messageData['id'] ?? '';
                    $messageTimestamp = $messageData['timestamp'] ?? time();
                    $messageContent = $messageData['message'] ?? [];

                    if (empty($fromPhone) || empty($waMessageId)) {
                        continue;
                    }

                    // Find the contact by phone number
                    $contact = Contact::fromTenant($subdomain)
                        ->where('phone', $fromPhone)
                        ->first();

                    if (! $contact) {
                        // Create contact if not exists
                        $contact = Contact::fromTenant($subdomain)->create([
                            'tenant_id' => $tenant_id,
                            'firstname' => 'WhatsApp',
                            'lastname' => 'User',
                            'phone' => $fromPhone,
                            'type' => 'lead',
                            'source_id' => $this->getOrCreateCoexistenceSource($tenant_id, $subdomain),
                            'status_id' => $this->getDefaultStatusId($tenant_id, $subdomain),
                            'addedfrom' => 0,
                            'description' => 'Auto-created from WhatsApp Business App message history',
                            'is_enabled' => true,
                        ]);
                    }

                    // Find or create chat
                    $chat = Chat::fromTenant($subdomain)
                        ->where('receiver_id', $fromPhone)
                        ->where('type', $contact->type)
                        ->where('type_id', $contact->id)
                        ->first();

                    if (! $chat) {
                        $chat = Chat::fromTenant($subdomain)->create([
                            'tenant_id' => $tenant_id,
                            'name' => $contact->firstname.' '.$contact->lastname,
                            'receiver_id' => $fromPhone,
                            'wa_no' => $toPhone, // Business phone number
                            'wa_no_id' => $phoneNumberId,
                            'type' => $contact->type,
                            'type_id' => $contact->id,
                            'last_message' => $this->extractMessageText($messageContent),
                            'last_msg_time' => \Carbon\Carbon::createFromTimestamp($messageTimestamp),
                            'time_sent' => \Carbon\Carbon::createFromTimestamp($messageTimestamp),
                        ]);
                    }

                    // Check if message already exists
                    $existingMessage = ChatMessage::fromTenant($subdomain)
                        ->where('message_id', $waMessageId)
                        ->first();

                    if (! $existingMessage) {
                        ChatMessage::fromTenant($subdomain)->create([
                            'tenant_id' => $tenant_id,
                            'interaction_id' => $chat->id,
                            'sender_id' => $fromPhone,
                            'message' => $this->extractMessageText($messageContent),
                            'message_id' => $waMessageId,
                            'time_sent' => \Carbon\Carbon::createFromTimestamp($messageTimestamp),
                            'type' => 'coexistence_history', // Mark as synced from history
                            'is_read' => true, // Historical messages are considered read
                        ]);

                        $synced++;
                    }

                } catch (Exception $e) {
                    Log::error('Failed to sync individual message', [
                        'message_data' => $messageData,
                        'error' => $e->getMessage(),
                        'tenant_id' => $tenant_id,
                    ]);
                    $errors++;
                }
            }

            // Update sync status
            $this->updateSyncStatus($tenant_id, $phoneNumberId, [
                'history_sync_status' => 'completed',
                'history_sync_completed_at' => now(),
                'total_messages_synced' => $synced,
            ]);

            ModuleEvents::trigger('coexistence_history_synced', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'synced_count' => $synced,
                'errors_count' => $errors,
            ]);

        } catch (Exception $e) {
            Log::error('Message history sync failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
            ]);

            $this->updateSyncStatus($tenant_id, $phoneNumberId, [
                'history_sync_status' => 'failed',
                'last_sync_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process real-time message echo from WhatsApp Business App
     */
    public function processMessageEcho(string $tenant_id, string $subdomain, array $echoData): void
    {
        try {
            $fromPhone = $this->normalizePhoneNumber($echoData['from'] ?? '');
            $toPhone = $this->normalizePhoneNumber($echoData['to'] ?? '');
            $waMessageId = $echoData['id'] ?? '';
            $messageContent = $echoData['message'] ?? [];

            if (empty($fromPhone) || empty($waMessageId)) {
                return;
            }

            // Find the contact
            $contact = Contact::fromTenant($subdomain)
                ->where('phone', $toPhone) // For echoes, 'to' is the customer
                ->first();

            if (! $contact) {
                // Create contact if not exists
                $contact = Contact::fromTenant($subdomain)->create([
                    'tenant_id' => $tenant_id,
                    'firstname' => 'WhatsApp',
                    'lastname' => 'User',
                    'phone' => $toPhone,
                    'type' => 'lead',
                    'source_id' => $this->getOrCreateCoexistenceSource($tenant_id, $subdomain),
                    'status_id' => $this->getDefaultStatusId($tenant_id, $subdomain),
                    'addedfrom' => 0,
                    'description' => 'Auto-created from WhatsApp Business App message echo',
                    'is_enabled' => true,
                ]);
            }

            // Find or create chat
            $chat = Chat::fromTenant($subdomain)
                ->where('receiver_id', $toPhone)
                ->where('type', $contact->type)
                ->where('type_id', $contact->id)
                ->first();

            if (! $chat) {
                $chat = Chat::fromTenant($subdomain)->create([
                    'tenant_id' => $tenant_id,
                    'name' => $contact->firstname.' '.$contact->lastname,
                    'receiver_id' => $toPhone,
                    'wa_no' => $fromPhone, // Business phone number
                    'wa_no_id' => $echoData['phone_number_id'] ?? '',
                    'type' => $contact->type,
                    'type_id' => $contact->id,
                    'last_message' => $this->extractMessageText($messageContent),
                    'last_msg_time' => now(),
                    'time_sent' => now(),
                ]);
            } else {
                // Update chat with latest message
                $chat->update([
                    'last_message' => $this->extractMessageText($messageContent),
                    'last_msg_time' => now(),
                ]);
            }

            // Add message echo to chat
            ChatMessage::fromTenant($subdomain)->create([
                'tenant_id' => $tenant_id,
                'interaction_id' => $chat->id,
                'sender_id' => $fromPhone, // Business sent the message
                'message' => $this->extractMessageText($messageContent),
                'message_id' => $waMessageId,
                'time_sent' => now(),
                'type' => 'coexistence_echo', // Mark as echo from business app
                'is_read' => true,
            ]);

            // Update sync status
            $this->updateSyncStatus($tenant_id, $echoData['phone_number_id'] ?? '', [
                'last_message_echo_at' => now(),
            ]);

            ModuleEvents::trigger('coexistence_message_echoes_received', [
                'tenant_id' => $tenant_id,
                'message_id' => $waMessageId,
                'contact_id' => $contact->id,
                'chat_id' => $chat->id,
            ]);

        } catch (Exception $e) {
            Log::error('Message echo processing failed', [
                'error' => $e->getMessage(),
                'echo_data' => $echoData,
                'tenant_id' => $tenant_id,
            ]);
        }
    }

    /**
     * Get or create coexistence source
     */
    protected function getOrCreateCoexistenceSource(string $tenant_id, string $subdomain): int
    {
        $source = Source::fromTenant($subdomain)
            ->where('name', 'WhatsApp Business App Coexistence')
            ->first();

        if (! $source) {
            $source = Source::fromTenant($subdomain)->create([
                'tenant_id' => $tenant_id,
                'name' => 'WhatsApp Business App Coexistence',
                'description' => 'Contacts synced from WhatsApp Business App via Coexistence feature',
            ]);
        }

        return $source->id;
    }

    /**
     * Get default status ID
     */
    protected function getDefaultStatusId(string $tenant_id, string $subdomain): int
    {
        $status = Status::fromTenant($subdomain)
            ->where('is_default', true)
            ->first();

        if (! $status) {
            // Create default status if none exists
            $status = Status::fromTenant($subdomain)->create([
                'tenant_id' => $tenant_id,
                'name' => 'New',
                'color' => '#007bff',
                'is_default' => true,
            ]);
        }

        return $status->id;
    }

    /**
     * Extract message text from message content
     */
    protected function extractMessageText(array $messageContent): string
    {
        if (isset($messageContent['text']['body'])) {
            return $messageContent['text']['body'];
        }

        if (isset($messageContent['body'])) {
            return $messageContent['body'];
        }

        if (isset($messageContent['type'])) {
            return ucfirst($messageContent['type']).' message';
        }

        return 'Message content';
    }

    /**
     * Normalize phone number format
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters except +
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);

        // Ensure it starts with + if it doesn't already
        if (! str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+'.$phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Update sync status
     */
    protected function updateSyncStatus(string $tenant_id, string $phoneNumberId, array $updates): void
    {
        if (empty($phoneNumberId)) {
            return;
        }

        CoexistenceSyncStatus::where('tenant_id', $tenant_id)
            ->where('phone_number_id', $phoneNumberId)
            ->update($updates);
    }

    /**
     * Check if coexistence is enabled for tenant
     */
    public function isCoexistenceEnabled(string $tenant_id): bool
    {
        return CoexistenceSyncStatus::where('tenant_id', $tenant_id)
            ->whereIn('contacts_sync_status', ['pending', 'in_progress', 'completed'])
            ->exists();
    }

    /**
     * Get coexistence sync status for tenant
     */
    public function getSyncStatus(string $tenant_id): ?CoexistenceSyncStatus
    {
        return CoexistenceSyncStatus::where('tenant_id', $tenant_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get synced contacts count
     */
    public function getSyncedContactsCount(string $tenant_id, string $subdomain): int
    {
        $coexistenceSourceId = $this->getOrCreateCoexistenceSource($tenant_id, $subdomain);

        return Contact::fromTenant($subdomain)
            ->where('source_id', $coexistenceSourceId)
            ->count();
    }

    /**
     * Get synced messages count
     */
    public function getSyncedMessagesCount(string $tenant_id, string $subdomain): int
    {
        return ChatMessage::fromTenant($subdomain)
            ->whereIn('type', ['coexistence_history', 'coexistence_echo'])
            ->count();
    }

    /**
     * Complete WhatsApp setup after coexistence
     */
    public function completeWhatsAppSetup(string $tenant_id, string $code): bool
    {
        try {
            Log::info('Starting WhatsApp setup completion', [
                'tenant_id' => $tenant_id,
                'code' => substr($code, 0, 20).'...',
            ]);

            // Get the sync status to get WABA ID and phone number
            $syncStatus = $this->getSyncStatus($tenant_id);
            if (! $syncStatus) {
                Log::error('No sync status found for tenant', ['tenant_id' => $tenant_id]);

                return false;
            }

            // Exchange authorization code for access token
            $accessToken = $this->exchangeCodeForAccessToken($code);
            if (! $accessToken) {
                Log::error('Failed to exchange code for access token');

                return false;
            }

            // Update tenant settings with WhatsApp configuration
            $this->updateTenantWhatsAppSettings($tenant_id, $accessToken, $syncStatus);

            Log::info('WhatsApp setup completed successfully', ['tenant_id' => $tenant_id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to complete WhatsApp setup', [
                'tenant_id' => $tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForAccessToken(string $code): ?string
    {
        try {
            $appId = get_setting('whatsapp.wm_fb_app_id');
            $appSecret = get_setting('whatsapp.wm_fb_app_secret');

            // Use the FacebookApiService to exchange code for token (same as regular embedded signup)
            $tokenResponse = $this->facebookApi->exchangeCodeForToken($code, $appId, $appSecret);

            if ($tokenResponse['success']) {
                return $tokenResponse['data']['access_token'] ?? null;
            }

            Log::error('Facebook API error during token exchange', [
                'error' => $tokenResponse['error'] ?? 'Unknown error',
                'status' => $tokenResponse['status'] ?? 'Unknown status',
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception during token exchange', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Update tenant settings with WhatsApp configuration
     */
    private function updateTenantWhatsAppSettings(string $tenant_id, string $accessToken, CoexistenceSyncStatus $syncStatus): void
    {
        // Use save_tenant_setting function to save WhatsApp settings
        save_tenant_setting('whatsapp', 'wm_access_token', $accessToken);
        save_tenant_setting('whatsapp', 'wm_business_account_id', $syncStatus->waba_id);
        save_tenant_setting('whatsapp', 'wm_default_phone_number_id', $syncStatus->phone_number_id);
        save_tenant_setting('whatsapp', 'is_whatsmark_connected', 1);
        save_tenant_setting('whatsapp', 'coexistence_completed_at', now()->toISOString());

        Log::info('Tenant WhatsApp settings updated', [
            'tenant_id' => $tenant_id,
            'waba_id' => $syncStatus->waba_id,
            'phone_number_id' => $syncStatus->phone_number_id,
        ]);
    }

    /**
     * Public method to manually trigger contacts sync
     */
    public function manualSyncContacts(string $tenant_id, string $phoneNumberId, string $wabaId): array
    {
        try {
            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);
            $accessToken = get_setting('whatsapp.wm_access_token', null, $tenant_id);

            if (! $accessToken) {
                return ['success' => false, 'message' => 'No access token found for tenant'];
            }

            $this->syncWhatsAppContacts($tenant_id, $subdomain, $phoneNumberId, $accessToken);

            return ['success' => true, 'message' => 'Contacts sync completed'];

        } catch (\Exception $e) {
            Log::error('Manual contacts sync failed', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Public method to manually trigger message history sync
     */
    public function manualSyncMessages(string $tenant_id, string $phoneNumberId, string $wabaId): array
    {
        try {
            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);
            $accessToken = get_setting('whatsapp.wm_access_token', null, $tenant_id);

            if (! $accessToken) {
                return ['success' => false, 'message' => 'No access token found for tenant'];
            }

            $this->syncWhatsAppMessages($tenant_id, $subdomain, $phoneNumberId, $accessToken);

            return ['success' => true, 'message' => 'Message history sync initiated'];

        } catch (\Exception $e) {
            Log::error('Manual message sync failed', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
