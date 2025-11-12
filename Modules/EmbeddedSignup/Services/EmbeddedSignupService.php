<?php

namespace Modules\EmbeddedSignup\Services;

use App\Traits\WhatsApp;
use Corbital\ModuleManager\Facades\ModuleEvents;
use Exception;

class EmbeddedSignupService
{
    use WhatsApp;

    protected $facebookApi;

    public function __construct(FacebookApiService $facebookApi)
    {
        $this->facebookApi = $facebookApi;
    }

    /**
     * Check if embedded signup is available for the current tenant
     */
    public function isEmbeddedSignupAvailable(): bool
    {
        if (! \Corbital\ModuleManager\Facades\ModuleManager::isActive('EmbeddedSignup')) {
            return false;
        }

        $appId = get_setting('whatsapp.wm_fb_app_id');
        $appSecret = get_setting('whatsapp.wm_fb_app_secret');

        if (! $appId || ! $appSecret) {
            return false;
        }

        $isConnected = get_tenant_setting_from_db('whatsapp', 'is_whatsmark_connected', 0);
        if ($isConnected) {
            return false;
        }

        return true;
    }

    /**
     * Process embedded signup completion
     */
    public function processEmbeddedSignup(array $signupData): array
    {
        try {
            $tenant_id = tenant_id();

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

            // Validate session data
            if (! $phoneNumberId || ! $waBaId || ! $code) {
                return [
                    'success' => false,
                    'error_code' => 'MISSING_SESSION_DATA',
                    'message' => 'Required data missing from Facebook embedded signup session',
                    'suggested_actions' => [
                        'Try the signup process again',
                        'Ensure you complete the Facebook authorization fully',
                        'Check that embedded signup flow is properly configured',
                    ],
                ];
            }

            // Exchange code for access token
            $tokenResponse = $this->facebookApi->exchangeCodeForToken($code, $appId, $appSecret);

            if (! $tokenResponse['success']) {
                return [
                    'success' => false,
                    'error_code' => 'TOKEN_EXCHANGE_FAILED',
                    'message' => 'Failed to exchange authorization code for access token',
                    'suggested_actions' => [
                        'Try the signup process again',
                        'Check if Facebook app credentials are correct',
                    ],
                ];
            }

            $accessToken = $tokenResponse['data']['access_token'];
            $tokenData = $tokenResponse['data'];

            // Use session data directly
            $wabaData = [
                'waba_id' => $waBaId,
                'waba_name' => 'Connected WABA',
                'phone_number_id' => $phoneNumberId,
                'display_phone_number' => 'Connected Number',
                'verified_name' => 'Business Account',
                'status' => 'CONNECTED',
                'source' => 'embedded_signup_session',
            ];

            // Try to get additional details from API
            try {
                $phoneDetails = $this->facebookApi->getPhoneNumberDetails($phoneNumberId, $accessToken);
                if ($phoneDetails['success']) {
                    $wabaData['display_phone_number'] = $phoneDetails['data']['display_phone_number'] ?? 'Connected Number';
                    $wabaData['verified_name'] = $phoneDetails['data']['verified_name'] ?? 'Business Account';
                    $wabaData['status'] = $phoneDetails['data']['status'] ?? 'CONNECTED';
                    $wabaData['platform_type'] = $phoneDetails['data']['platform_type'] ?? 'UNKNOWN';
                }
            } catch (Exception $e) {
                // Continue with basic data if API call fails
            }

            // Register phone number for Cloud API if needed
            $this->registerPhoneNumberIfNeeded($phoneNumberId, $accessToken);

            // Configure webhooks
            $webhookConfigured = $this->configureWebhooks($waBaId, $accessToken, $tenant_id);

            // Save settings
            $this->saveTenantSettings($accessToken, $wabaData, $tokenData, [
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $waBaId,
                'business_id' => $businessId,
                'original_signup_data' => $signupData,
            ]);

            // Load templates
            $templatesLoaded = $this->loadTemplates($tenant_id);

            // Mark as connected
            $this->markAsConnected($webhookConfigured);

            // Fire success event
            ModuleEvents::trigger('embedded_signup_completed', [
                'waba_data' => $wabaData,
                'token_data' => $tokenData,
                'tenant_id' => $tenant_id,
            ]);

            return [
                'success' => true,
                'message' => 'WhatsApp Business Account connected successfully',
                'data' => [
                    'waba_id' => $waBaId,
                    'phone_number_id' => $phoneNumberId,
                    'display_phone_number' => $wabaData['display_phone_number'],
                    'verified_name' => $wabaData['verified_name'],
                    'templates_synced' => $templatesLoaded,
                    'webhook_configured' => $webhookConfigured,
                    'data_source' => 'embedded_signup_session',
                ],
            ];
        } catch (Exception $e) {
            ModuleEvents::trigger('embedded_signup_failed', [
                'signup_data' => $signupData,
                'error' => $e->getMessage(),
                'tenant_id' => tenant_id(),
            ]);

            return [
                'success' => false,
                'error_code' => 'SYSTEM_EXCEPTION',
                'message' => 'System error during embedded signup process',
                'suggested_actions' => [
                    'Try again in a few moments',
                    'Contact technical support if the issue persists',
                ],
            ];
        }
    }

    /**
     * Register phone number if needed
     */
    private function registerPhoneNumberIfNeeded(string $phoneNumberId, string $accessToken): void
    {
        try {
            $phoneResponse = $this->facebookApi->getPhoneNumberDetails($phoneNumberId, $accessToken);

            if (
                $phoneResponse['success'] &&
                isset($phoneResponse['data']['platform_type']) &&
                $phoneResponse['data']['platform_type'] !== 'CLOUD_API'
            ) {

                $this->facebookApi->registerPhoneNumber($phoneNumberId, $accessToken);
            }
        } catch (Exception $e) {
            // Continue if registration fails
        }
    }

    /**
     * Configure webhooks
     */
    private function configureWebhooks(string $wabaId, string $accessToken, string $tenantId): bool
    {
        try {
            $webhookUrl = route('whatsapp.webhook');
            $verifyToken = get_setting('whatsapp.webhook_verify_token') ?? config('app.name').'_'.$tenantId;

            $webhookResponse = $this->facebookApi->subscribeToWebhooks($wabaId, $accessToken, $webhookUrl, $verifyToken);

            return $webhookResponse['success'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Load templates
     */
    private function loadTemplates(string $tenantId): bool
    {
        try {
            $this->setWaTenantId($tenantId);
            $templatesResponse = $this->loadTemplatesFromWhatsApp();

            return $templatesResponse['status'] ?? false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mark account as connected
     */
    private function markAsConnected(bool $webhookConfigured): void
    {
        save_tenant_setting('whatsapp', 'is_whatsmark_connected', 1);
        save_tenant_setting('whatsapp', 'is_webhook_connected', $webhookConfigured ? 1 : 0);
        save_tenant_setting('whatsapp', 'embedded_signup_completed_at', now()->toISOString());
    }

    /**
     * Check if embedded signup is available with detailed information
     */
    public function checkAvailability(): array
    {
        $whatsapp_settings = tenant_settings_by_group('whatsapp');
        $enabled = \Corbital\ModuleManager\Facades\ModuleManager::isActive('EmbeddedSignup');
        $appId = get_setting('whatsapp.wm_fb_app_id');
        $appSecret = get_setting('whatsapp.wm_fb_app_secret');
        $configId = get_setting('whatsapp.wm_fb_config_id');
        $redirectUrl = tenant_route('tenant.waba.embedded.callback');
        $configured = ! empty($appId) && ! empty($appSecret) && ! empty($configId);
        $isConnected = $whatsapp_settings['is_whatsmark_connected'] ?? '0';
        $available = $enabled && $configured && ! $isConnected;

        return [
            'available' => $available,
            'enabled' => $enabled,
            'configured' => $configured,
            'connected' => $isConnected,
            'app_id' => $appId,
            'config_id' => $configId,
            'redirect_url' => $redirectUrl,
            'configuration_details' => [
                'app_id_present' => ! empty($appId),
                'app_secret_present' => ! empty($appSecret),
                'config_id_present' => ! empty($configId),
            ],
        ];
    }

    /**
     * Generate embedded signup URL
     */
    public function generateSignupUrl(array $options = []): string
    {
        $appId = get_setting('whatsapp.wm_fb_app_id');
        $configId = get_setting('whatsapp.wm_fb_config_id');

        $params = array_merge([
            'config_id' => $configId,
            'response_type' => 'code',
            'override_default_response_type' => 'true',
            'extras' => json_encode([
                'feature' => 'whatsapp_embedded_signup',
                'version' => '2',
            ]),
        ], $options);

        return 'https://www.facebook.com/v21.0/dialog/oauth?'.http_build_query(array_filter($params));
    }

    /**
     * Validate webhook verification
     */
    public function validateWebhookVerification(string $mode, string $token, string $challenge)
    {
        $verifyToken = get_setting('whatsapp.webhook_verify_token') ??
            config('app.name').'_'.tenant_id();

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }

        return false;
    }

    /**
     * Save settings to tenant
     */
    protected function saveTenantSettings(string $accessToken, array $wabaData, array $tokenData, array $signupData): void
    {
        save_tenant_setting('whatsapp', 'wm_access_token', $accessToken);
        save_tenant_setting('whatsapp', 'wm_business_account_id', $wabaData['waba_id']);
        save_tenant_setting('whatsapp', 'wm_default_phone_number_id', $wabaData['phone_number_id']);
        save_tenant_setting('whatsapp', 'wm_default_phone_number', $wabaData['display_phone_number']);
        save_tenant_setting('whatsapp', 'wm_phone_number_verified_name', $wabaData['verified_name']);

        save_tenant_setting('whatsapp', 'embedded_signup_data', json_encode([
            'waba_name' => $wabaData['waba_name'],
            'token_data' => $tokenData,
            'signup_data' => $signupData,
            'completed_at' => now()->toISOString(),
            'data_source' => 'embedded_signup_session',
        ]));

        $appId = get_setting('whatsapp.wm_fb_app_id');
        if ($appId) {
            save_tenant_setting('whatsapp', 'wm_fb_app_id', $appId);
        }

        $appSecret = get_setting('whatsapp.wm_fb_app_secret');
        if ($appSecret) {
            save_tenant_setting('whatsapp', 'wm_fb_app_secret', $appSecret);
        }

        $configId = get_setting('whatsapp.wm_fb_config_id');
        if ($configId) {
            save_tenant_setting('whatsapp', 'wm_fb_config_id', $configId);
        }
    }
}
