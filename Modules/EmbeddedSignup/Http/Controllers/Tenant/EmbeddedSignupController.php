<?php

namespace Modules\EmbeddedSignup\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EmbeddedSignup\Services\EmbeddedSignupService;

class EmbeddedSignupController extends Controller
{
    protected $embeddedSignupService;

    public function __construct(EmbeddedSignupService $embeddedSignupService)
    {
        $this->embeddedSignupService = $embeddedSignupService;
    }

    /**
     * Handle the embedded signup callback from Facebook
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        if ($error) {
            whatsapp_log('Embedded signup callback error', 'error', [
                'error' => $error,
                'error_description' => $errorDescription,
            ]);

            return redirect(tenant_route('tenant.embedded-signup.embsignin'))
                ->with('error', 'Embedded signup failed: '.$errorDescription);
        }

        if (! $code) {
            return redirect(tenant_route('tenant.embedded-signup.embsignin'))
                ->with('error', 'Authorization code missing');
        }

        // Log the callback for debugging
        whatsapp_log('Embedded signup callback received', 'info', [
            'code' => substr($code, 0, 20).'...',
        ]);

        return redirect(tenant_route('tenant.embedded-signup.embsignin'))
            ->with('info', 'Processing signup data...');
    }

    /**
     * Check if embedded signup is available for the current tenant
     */
    public function availability()
    {
        try {
            $available = $this->embeddedSignupService->isEmbeddedSignupAvailable();

            return response()->json([
                'available' => $available,
                'message' => $available ? 'Embedded signup is available' : 'Embedded signup is not available',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Error checking availability',
            ], 500);
        }
    }
}
