<?php

namespace Modules\EmbeddedSignup\Livewire\Tenant;

use Livewire\Component;
use Modules\EmbeddedSignup\Services\EmbeddedSignupService;
use Illuminate\Support\Facades\Log;  // ← TAMBAHKAN INI
use Illuminate\Support\Facades\Http; // ← TAMBAHKAN INI

class EmbeddedSignupFlow extends Component
{
    public $currentStep = 'initial';

    public $isProcessing = false;
    
    public $connectionType = 'new'; // ← TAMBAH BARIS INI!

    public $errorMessage = '';

    public $errorCode = '';

    public $errorType = '';

    public $errorSeverity = '';

    public $errorDetails = [];

    public $technicalDetails = [];

    public $suggestedActions = [];

    public $successMessage = '';

    public $signupData = [];

    public $availability = [];

    protected $embeddedSignupService;

    public function mount()
    {

        $tenant_id = tenant_id();
        $data = [];

        if ($tenant_id) {
            $subscription = \App\Models\Subscription::where('tenant_id', $tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();

            if ($subscription) {
                $data = \App\Models\PlanFeature::where('plan_id', $subscription->plan_id)->pluck('slug')->toArray();
            }
        }
        if (! in_array('emb_signup', $data)) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $this->embeddedSignupService = app(EmbeddedSignupService::class);
        $this->availability = $this->embeddedSignupService->checkAvailability();

        // Check if user has permission
        if (! checkPermission('tenant.connect_account.connect')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        // Check if already connected
        $whatsappSettings = tenant_settings_by_group('whatsapp');
        if (($whatsappSettings['is_whatsmark_connected'] ?? 0) == 1 && ($whatsappSettings['is_webhook_connected'] ?? 0) == 1) {
            return redirect()->to(tenant_route('tenant.waba'));
        }
    }

    public function launchEmbeddedSignup()
    {
        if (! $this->availability['available']) {
            $this->setError('CONFIGURATION_ERROR', 'configuration', 'critical', t('embedded_signup_not_available'), [], [], [
                'Check admin configuration',
                'Verify Facebook app credentials are set',
            ]);

            return;
        }

        $this->currentStep = 'launching';
        $this->isProcessing = true;
        $this->clearErrors();

        // Get Facebook configuration from admin settings
        $appId = get_setting('whatsapp.wm_fb_app_id');
        $configId = get_setting('whatsapp.wm_fb_config_id');
        $redirectUrl = tenant_route('tenant.waba.embedded.callback');

        // Fallback to availability data if settings not available
        if (! $appId) {
            $appId = $this->availability['app_id'] ?? '';
        }
        if (! $configId) {
            $configId = $this->availability['config_id'] ?? '';
        }

        if (! $appId || ! $configId) {
            $this->setError('MISSING_CONFIGURATION', 'configuration', 'critical', 'Facebook app configuration is incomplete', [
                'app_id_present' => ! empty($appId),
                'config_id_present' => ! empty($configId),
            ], [], [
                'Contact administrator to configure Facebook app settings',
                'Verify app ID and config ID are properly set',
            ]);
            $this->currentStep = 'error';
            $this->isProcessing = false;

            return;
        }

        // The actual Facebook dialog will be launched via JavaScript
        $this->dispatch('launch-facebook-dialog', [
            'app_id' => $appId,
            'config_id' => $configId,
            'redirect_url' => $redirectUrl,
            'connection_type' => $this->connectionType, // TAMBAH INI
        ]);
    }

    public function processSignupResponse($responseData)
    {
        // Ensure service is always initialized (fix for Livewire hydration issue)
        if ($this->embeddedSignupService === null) {
            $this->embeddedSignupService = app(\Modules\EmbeddedSignup\Services\EmbeddedSignupService::class);
        }

        $this->currentStep = 'processing';
        $this->isProcessing = true;
        $this->clearErrors();

        try {
            // Log the processing attempt
            whatsapp_log('Livewire processing signup response - STRICT MODE', 'info', [
                'response_data_keys' => array_keys($responseData),
                'has_auth_response' => ! empty($responseData['authResponse']),
                'has_phone_number_id' => ! empty($responseData['phoneNumberId']),
                'has_waba_id' => ! empty($responseData['waBaId']),
                'tenant_id' => tenant_id(),
            ]);

            $result = $this->embeddedSignupService->processEmbeddedSignup($responseData);

            if ($result['success']) {
                $this->handleSuccessResponse($result);
            } else {
                $this->handleErrorResponse($result);
            }
        } catch (\Exception $e) {
            // This should not happen with the new strict service, but just in case
            $this->handleSystemException($e, $responseData);
        }

        $this->isProcessing = false;
    }

    /**
     * Handle successful signup response
     */
    private function handleSuccessResponse(array $result): void
    {
        $this->successMessage = $result['message'];
        $this->signupData = $result['data'];
        $this->currentStep = 'success';

        whatsapp_log('Embedded signup completed successfully in Livewire', 'info', [
            'waba_id' => $result['data']['waba_id'] ?? 'unknown',
            'phone_number_id' => $result['data']['phone_number_id'] ?? 'unknown',
            'data_source' => $result['data']['data_source'] ?? 'unknown',
        ]);

        // Redirect to WABA dashboard after a brief delay
        $this->dispatch('signup-completed', [
            'redirect_url' => tenant_route('tenant.waba'),
            'delay' => 2000,
        ]);
    }

    /**
     * Handle error response from strict service
     */
    private function handleErrorResponse(array $result): void
    {
        $this->setError(
            $result['error_code'] ?? 'UNKNOWN_ERROR',
            $result['error_type'] ?? 'unknown',
            $result['error_severity'] ?? 'high',
            $result['message'] ?? 'An unknown error occurred',
            $result['details'] ?? [],
            $result['technical_details'] ?? [],
            $result['suggested_actions'] ?? ['Try again later', 'Contact support if issue persists']
        );

        $this->currentStep = 'error';

        // Log the structured error
        whatsapp_log('Embedded signup failed with structured error', 'error', [
            'error_code' => $result['error_code'] ?? 'UNKNOWN',
            'error_type' => $result['error_type'] ?? 'unknown',
            'error_severity' => $result['error_severity'] ?? 'high',
            'message' => $result['message'] ?? 'Unknown error',
            'tenant_id' => tenant_id(),
        ]);
    }

    /**
     * Handle system exceptions (shouldn't happen with strict service)
     */
    private function handleSystemException(\Exception $e, array $responseData): void
    {
        $this->setError(
            'LIVEWIRE_EXCEPTION',
            'system',
            'critical',
            'An unexpected system error occurred during signup processing',
            [
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ],
            [
                'exception_class' => get_class($e),
                'response_data' => $responseData,
            ],
            [
                'This is a system-level error that should not occur',
                'Contact technical support immediately',
                'Provide this error information to the support team',
            ]
        );

        $this->currentStep = 'error';

        whatsapp_log('Livewire system exception during signup processing', 'critical', [
            'exception_message' => $e->getMessage(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'response_data' => $responseData,
            'tenant_id' => tenant_id(),
        ], $e);
    }

    public function resetFlow()
    {
        $this->currentStep = 'initial';
        $this->isProcessing = false;
        $this->clearErrors();
        $this->successMessage = '';
        $this->signupData = [];
    }

    public function retrySignup()
    {
        $this->resetFlow();
    }

    /**
     * Set error with enhanced structure
     */
    protected function setError(string $code, string $type, string $severity, string $message, array $details = [], array $technicalDetails = [], array $actions = [])
    {
        $this->errorCode = $code;
        $this->errorType = $type;
        $this->errorSeverity = $severity;
        $this->errorMessage = $message;
        $this->errorDetails = $details;
        $this->technicalDetails = $technicalDetails;
        $this->suggestedActions = $actions;
    }

    protected function clearErrors()
    {
        $this->errorCode = '';
        $this->errorType = '';
        $this->errorSeverity = '';
        $this->errorMessage = '';
        $this->errorDetails = [];
        $this->technicalDetails = [];
        $this->suggestedActions = [];
    }

    /**
     * Get error severity level for UI styling
     */
    public function getErrorSeverityLevel()
    {
        switch ($this->errorSeverity) {
            case 'critical':
                return 'critical';
            case 'high':
                return 'high';
            case 'medium':
                return 'medium';
            case 'low':
                return 'low';
            default:
                return 'medium';
        }
    }

    /**
     * Check if error should show contact admin button
     */
    public function shouldShowContactAdmin()
    {
        return in_array($this->errorType, ['configuration']) ||
            in_array($this->errorCode, ['1001', '1002', '1003', '1004']) ||
            $this->errorSeverity === 'critical';
    }

    /**
     * Check if error allows retry
     */
    public function canRetry()
    {
        return ! in_array($this->errorType, ['configuration']) &&
            $this->errorSeverity !== 'critical';
    }

    /**
     * Get error icon based on severity
     */
    public function getErrorIcon()
    {
        switch ($this->errorSeverity) {
            case 'critical':
                return 'heroicon-o-x-circle';
            case 'high':
                return 'heroicon-o-exclamation-triangle';
            case 'medium':
                return 'heroicon-o-exclamation-circle';
            case 'low':
                return 'heroicon-o-information-circle';
            default:
                return 'heroicon-o-exclamation-triangle';
        }
    }

    /**
     * Get CSS classes for error display
     */
    public function getErrorClasses()
    {
        switch ($this->errorSeverity) {
            case 'critical':
                return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border-red-300';
            case 'high':
                return 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 border-orange-300';
            case 'medium':
                return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border-yellow-300';
            case 'low':
                return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 border-blue-300';
            default:
                return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border-yellow-300';
        }
    }

    public function render()
    {
        return view('embeddedsignup::livewire.tenant.embedded-signup-flow', [
            'availability' => $this->availability,
        ]);
    }
}