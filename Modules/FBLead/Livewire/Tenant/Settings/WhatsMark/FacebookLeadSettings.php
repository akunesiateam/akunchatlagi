<?php

namespace Modules\FBLead\Livewire\Tenant\Settings\WhatsMark;

use App\Models\Tenant\CustomField;
use App\Models\Tenant\Group;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\FBLead\Services\FacebookGraphApiService;

class FacebookLeadSettings extends Component
{
    public ?bool $fb_lead_enabled = false;

    public $fb_lead_status = null;

    public $fb_lead_source = null;

    public $fb_lead_assigned_to = null;

    public $fb_lead_group = null;

    public $fb_app_id = null;

    public $fb_app_secret = null;

    public $fb_webhook_verify_token = null;

    public $fb_pages = [];

    public $fb_access_token = null;

    public $isLoading = false;

    public $originalAppId = null;

    public $originalAppSecret = null;

    protected function rules()
    {
        return [
            'fb_lead_enabled' => 'nullable|boolean',
            'fb_lead_status' => 'nullable|numeric|exists:statuses,id|required_if:fb_lead_enabled,true',
            'fb_lead_source' => 'nullable|numeric|exists:sources,id|required_if:fb_lead_enabled,true',
            'fb_lead_assigned_to' => 'nullable|numeric|exists:users,id',
            'fb_lead_group' => 'nullable|string|max:255', // Changed to string for comma-separated group IDs
            'fb_app_id' => 'nullable|string|max:255|required_if:fb_lead_enabled,true',
            'fb_app_secret' => 'nullable|string|max:255|required_if:fb_lead_enabled,true',
            'fb_webhook_verify_token' => 'nullable|string|max:255',
            'fb_access_token' => 'nullable|string|max:500',
        ];
    }

    public function mount()
    {
        if (! checkPermission('tenant.whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $settings = tenant_settings_by_group('facebook-lead');

        $this->fb_lead_enabled = $settings['fb_lead_enabled'] ?? false;
        $this->fb_lead_status = $settings['fb_lead_status'] ?? null;
        $this->fb_lead_source = $settings['fb_lead_source'] ?? null;
        $this->fb_lead_assigned_to = $settings['fb_lead_assigned_to'] ?? null;
        $this->fb_lead_group = $settings['fb_lead_group'] ?? null;
        $this->fb_app_id = $settings['fb_app_id'] ?? null;
        $this->fb_app_secret = $settings['fb_app_secret'] ?? null;
        $this->fb_webhook_verify_token = $settings['fb_webhook_verify_token'] ?? null;
        $this->fb_access_token = $settings['fb_access_token'] ?? null;

        // Store original values to detect changes
        $this->originalAppId = $this->fb_app_id;
        $this->originalAppSecret = $this->fb_app_secret;

        // Handle fb_pages - could be JSON string, array, or null
        $fbPagesData = $settings['fb_pages'] ?? '[]';

        try {
            if (is_array($fbPagesData)) {
                $this->fb_pages = $fbPagesData;
            } elseif (is_string($fbPagesData) && ! empty($fbPagesData)) {
                $decoded = json_decode($fbPagesData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('Failed to decode fb_pages JSON', [
                        'tenant_id' => tenant_id(),
                        'data' => $fbPagesData,
                        'error' => json_last_error_msg(),
                    ]);
                    $this->fb_pages = [];
                } else {
                    $this->fb_pages = $decoded ?? [];
                }
            } else {
                $this->fb_pages = [];
            }
        } catch (\Exception $e) {
            Log::error('Error handling fb_pages data', [
                'tenant_id' => tenant_id(),
                'data_type' => gettype($fbPagesData),
                'error' => $e->getMessage(),
            ]);
            $this->fb_pages = [];
        }
    }

    public function generateWebhookToken()
    {
        $this->fb_webhook_verify_token = Str::random(32);
        $this->notify([
            'type' => 'success',
            'message' => t('webhook_verify_token_generated'),
        ]);
    }

    public function updatedFbAppId($value)
    {
        $this->checkAppCredentialsChanged();
    }

    public function updatedFbAppSecret($value)
    {
        $this->checkAppCredentialsChanged();
    }

    private function checkAppCredentialsChanged()
    {
        if (($this->originalAppId && $this->originalAppId !== $this->fb_app_id) ||
            ($this->originalAppSecret && $this->originalAppSecret !== $this->fb_app_secret)) {

            // Clear existing pages and tokens when credentials change
            $this->fb_pages = [];
            $this->fb_access_token = null;

            // Clear from database
            save_tenant_setting('facebook-lead', 'fb_pages', '[]');
            save_tenant_setting('facebook-lead', 'fb_access_token', null);

            $this->notify([
                'type' => 'info',
                'message' => t('fb_credentials_changed_cleared'),
            ]);

            // Update original values
            $this->originalAppId = $this->fb_app_id;
            $this->originalAppSecret = $this->fb_app_secret;
        }
    }

    public function fetchFacebookPages()
    {
        if (! $this->fb_app_id || ! $this->fb_app_secret) {
            $this->notify([
                'type' => 'danger',
                'message' => t('fb_app_credentials_required'),
            ]);

            return;
        }

        if ($this->isLoading) {
            return; // Prevent multiple simultaneous requests
        }

        $this->isLoading = true;

        try {
            // Clear old subscriptions and data when refetching
            $this->fb_pages = [];
            $this->fb_access_token = null;

            // Clear from database
            save_tenant_setting('facebook-lead', 'fb_pages', '[]');
            save_tenant_setting('facebook-lead', 'fb_access_token', null);

            // Emit event to trigger Facebook login on frontend
            $this->dispatch('initiateFacebookLogin', [
                'appId' => $this->fb_app_id,
                'permissions' => 'pages_manage_ads,pages_manage_metadata,pages_read_engagement,ads_management,leads_retrieval',
            ]);

            $this->notify([
                'type' => 'info',
                'message' => t('fb_old_subscriptions_cleared'),
            ]);

        } catch (\Exception $e) {
            $this->isLoading = false;
            $this->notify([
                'type' => 'danger',
                'message' => t('fb_pages_fetch_failed').': '.$e->getMessage(),
            ]);
        }
    }

    public function handleFacebookToken($token)
    {
        try {
            if (! $this->fb_app_id || ! $this->fb_app_secret) {
                throw new \Exception(t('fb_app_credentials_not_configured'));
            }

            $facebookService = new FacebookGraphApiService($this->fb_app_id, $this->fb_app_secret);

            // Exchange short-lived token for long-lived token
            $tokenData = $facebookService->exchangeToken($token);

            if (! $tokenData || ! isset($tokenData['access_token'])) {
                throw new \Exception(t('fb_token_exchange_failed'));
            }

            $longLivedToken = $tokenData['access_token'];

            // Validate the token
            $userInfo = $facebookService->validateAccessToken($longLivedToken);
            if (! $userInfo) {
                throw new \Exception(t('fb_invalid_access_token'));
            }

            // Fetch pages
            $pages = $facebookService->fetchPages($longLivedToken);
            if ($pages === null) {
                throw new \Exception(t('fb_pages_fetch_error'));
            }

            // Store token and pages
            $this->fb_access_token = $longLivedToken;
            $this->fb_pages = $pages;

            // Save to settings
            save_tenant_setting('facebook-lead', 'fb_access_token', $longLivedToken);
            save_tenant_setting('facebook-lead', 'fb_pages', json_encode($pages));

            $this->notify([
                'type' => 'success',
                'message' => t('fb_pages_fetched_successfully', ['count' => count($pages)]),
            ]);

            // Update original credentials to current ones
            $this->originalAppId = $this->fb_app_id;
            $this->originalAppSecret = $this->fb_app_secret;

        } catch (\Exception $e) {
            Log::error('Facebook pages fetch error: '.$e->getMessage());

            $this->notify([
                'type' => 'danger',
                'message' => t('fb_pages_fetch_failed').': '.$e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function subscribeToPage($pageId)
    {
        try {
            if (! $this->fb_access_token) {
                throw new \Exception(t('fb_no_access_token'));
            }

            $page = collect($this->fb_pages)->firstWhere('id', $pageId);
            if (! $page) {
                throw new \Exception(t('fb_page_not_found'));
            }

            $facebookService = new FacebookGraphApiService($this->fb_app_id, $this->fb_app_secret);
            $success = $facebookService->subscribePageToApp($pageId, $page['access_token']);

            if ($success) {
                // Update page status in local array
                $this->fb_pages = collect($this->fb_pages)->map(function ($p) use ($pageId) {
                    if ($p['id'] === $pageId) {
                        $p['subscribed'] = true;
                    }

                    return $p;
                })->toArray();

                // Save updated pages
                save_tenant_setting('facebook-lead', 'fb_pages', json_encode($this->fb_pages));

                $this->notify([
                    'type' => 'success',
                    'message' => t('fb_page_subscribed_successfully'),
                ]);
            } else {
                throw new \Exception(t('fb_subscription_failed'));
            }

        } catch (\Exception $e) {
            Log::error('Facebook page subscription error: '.$e->getMessage());

            $this->notify([
                'type' => 'danger',
                'message' => t('fb_page_subscription_failed').': '.$e->getMessage(),
            ]);
        }
    }

    public function unsubscribeFromPage($pageId)
    {
        try {
            if (! $this->fb_access_token) {
                throw new \Exception(t('fb_no_access_token'));
            }

            $page = collect($this->fb_pages)->firstWhere('id', $pageId);
            if (! $page) {
                throw new \Exception(t('fb_page_not_found'));
            }

            $facebookService = new FacebookGraphApiService($this->fb_app_id, $this->fb_app_secret);
            $success = $facebookService->unsubscribePageFromApp($pageId, $page['access_token']);

            if ($success) {
                // Update page status in local array
                $this->fb_pages = collect($this->fb_pages)->map(function ($p) use ($pageId) {
                    if ($p['id'] === $pageId) {
                        $p['subscribed'] = false;
                    }

                    return $p;
                })->toArray();

                // Save updated pages
                save_tenant_setting('facebook-lead', 'fb_pages', json_encode($this->fb_pages));

                $this->notify([
                    'type' => 'success',
                    'message' => t('fb_page_unsubscribed_successfully'),
                ]);
            } else {
                throw new \Exception(t('fb_unsubscription_failed'));
            }

        } catch (\Exception $e) {
            Log::error('Facebook page unsubscription error: '.$e->getMessage());

            $this->notify([
                'type' => 'danger',
                'message' => t('fb_page_unsubscription_failed').': '.$e->getMessage(),
            ]);
        }
    }

    public function save()
    {
        if (checkPermission('tenant.whatsmark_settings.edit')) {
            $this->validate();

            // Additional validation for Facebook Lead integration
            if ($this->fb_lead_enabled) {
                // Check if at least one page is subscribed
                $hasSubscribedPage = collect($this->fb_pages)->contains('subscribed', true);

                if (! $hasSubscribedPage) {
                    $this->addError('fb_pages', t('fb_at_least_one_page_required'));

                    return;
                }
            }

            $settings = tenant_settings_by_group('facebook-lead');

            $originalSettings = [
                'fb_lead_enabled' => $settings['fb_lead_enabled'] ?? false,
                'fb_lead_status' => $settings['fb_lead_status'] ?? null,
                'fb_lead_source' => $settings['fb_lead_source'] ?? null,
                'fb_lead_assigned_to' => $settings['fb_lead_assigned_to'] ?? null,
                'fb_lead_group' => $settings['fb_lead_group'] ?? null,
                'fb_app_id' => $settings['fb_app_id'] ?? null,
                'fb_app_secret' => $settings['fb_app_secret'] ?? null,
                'fb_webhook_verify_token' => $settings['fb_webhook_verify_token'] ?? null,
                'fb_access_token' => $settings['fb_access_token'] ?? null,
                'fb_pages' => $settings['fb_pages'] ?? [],
            ];

            $modifiedSettings = [];

            foreach ($originalSettings as $key => $originalValue) {
                $newValue = $this->{$key};

                // Special handling for fb_pages array comparison
                if ($key === 'fb_pages') {
                    $originalPages = is_array($originalValue) ? $originalValue : (json_decode($originalValue, true) ?? []);
                    $newPages = is_array($newValue) ? $newValue : [];

                    if (json_encode($originalPages) !== json_encode($newPages)) {
                        $modifiedSettings[$key] = ! empty($newPages) ? json_encode($newPages) : null;
                    }
                } elseif ($key === 'fb_webhook_verify_token') {
                    // Always save webhook token if provided (to handle token sync issues)
                    if (! empty($newValue)) {
                        $modifiedSettings[$key] = $newValue;
                    }
                } else {
                    // If the value is new or changed, mark it for saving
                    if (! array_key_exists($key, $settings) || $originalValue !== $newValue) {
                        $modifiedSettings[$key] = ! empty($newValue) ? $newValue : null;
                    }
                }
            }

            if (! empty($modifiedSettings)) {
                foreach ($modifiedSettings as $key => $value) {
                    save_tenant_setting('facebook-lead', $key, $value);
                }

                $this->notify([
                    'type' => 'success',
                    'message' => t('setting_save_successfully'),
                ]);

                // Dispatch event to refresh the component
                $this->dispatch('refresh-groups', ['selectedGroups' => $this->fb_lead_group]);
            }
        }
    }

    /**
     * Check if Facebook Lead integration is enabled for the current tenant
     */
    public function isFacebookLeadEnabled(): bool
    {
        return $this->fb_lead_enabled &&
               ! empty($this->fb_app_id) &&
               ! empty($this->fb_app_secret) &&
               ! empty($this->fb_webhook_verify_token);
    }

    /**
     * Get the webhook URL for Facebook verification
     */
    public function getWebhookUrl(): string
    {
        $subdomain = tenant_subdomain();

        try {
            // Use the new facebook-leads route
            return route('facebook-webhook.leads', ['tenant' => $subdomain]);
        } catch (\Exception $e) {
            // Fallback to manual URL construction
            $baseUrl = config('app.url');
            $baseUrl = rtrim($baseUrl, '/');

            return "{$baseUrl}/webhooks/facebook/{$subdomain}/facebook-leads";
        }
    }

    /**
     * Test Facebook connection and permissions
     */
    public function testFacebookConnection()
    {
        if (! $this->fb_access_token || ! $this->fb_app_id || ! $this->fb_app_secret) {
            $this->notify([
                'type' => 'danger',
                'message' => t('fb_connection_not_configured'),
            ]);

            return;
        }

        try {
            $facebookService = new FacebookGraphApiService($this->fb_app_id, $this->fb_app_secret);
            $userInfo = $facebookService->validateAccessToken($this->fb_access_token);

            if ($userInfo) {
                $this->notify([
                    'type' => 'success',
                    'message' => t('fb_connection_test_successful'),
                ]);
            } else {
                $this->notify([
                    'type' => 'danger',
                    'message' => t('fb_connection_test_failed'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Facebook connection test failed', [
                'tenant_id' => tenant_id(),
                'error' => $e->getMessage(),
            ]);

            $this->notify([
                'type' => 'danger',
                'message' => t('fb_connection_test_failed').': '.$e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('fblead::livewire.tenant.settings.whats-mark.facebook-lead-settings', [
            'statuses' => Status::where('tenant_id', tenant_id())->get(),
            'sources' => Source::where('tenant_id', tenant_id())->get(),
            'users' => User::where('tenant_id', tenant_id())->get(),
            'groups' => Group::where('tenant_id', tenant_id())->get(),
            'availableCustomFields' => CustomField::where('tenant_id', tenant_id())
                ->where('is_active', true)
                ->orderBy('field_label')
                ->get(),
        ]);
    }
}
