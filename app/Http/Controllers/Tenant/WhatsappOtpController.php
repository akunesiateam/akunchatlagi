<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\WhatsappTemplate;
use App\Services\OtpService;
use App\Traits\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsappOtpController extends Controller
{
    use WhatsApp;

    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP via WhatsApp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'template_id' => 'required|integer|exists:whatsapp_templates,id',
            'otp_code' => 'nullable|string|min:4|max:8',
            'otp_length' => 'nullable|integer|min:4|max:8',
            'store_for_verification' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = tenant_id();
            $phone = $request->input('phone');

            // Get template
            $template = WhatsappTemplate::where('id', $request->input('template_id'))
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found',
                ], 404);
            }

            // Verify template is authentication type
            if (! $template->isAuthenticationTemplate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template is not an authentication template',
                ], 400);
            }

            // Check template status
            if ($template->status !== 'APPROVED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Template is not approved yet',
                ], 400);
            }

            // Check rate limit
            $rateLimit = $this->otpService->checkRateLimit($phone);
            if (! $rateLimit['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $rateLimit['message'],
                    'reason' => $rateLimit['reason'],
                    'retry_after_seconds' => $rateLimit['retry_after_seconds'],
                ], 429);
            }

            // Generate or use provided OTP code
            if ($request->has('otp_code')) {
                $otpCode = $request->input('otp_code');

                // Validate format
                if (! $this->otpService->validateFormat($otpCode, strlen($otpCode))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid OTP code format',
                    ], 400);
                }
            } else {
                $otpLength = $request->input('otp_length', 6);
                $otpCode = $this->otpService->generate($otpLength, false);
            }

            // Store OTP for verification if requested
            $storeForVerification = $request->input('store_for_verification', true);
            if ($storeForVerification) {
                $expiryMinutes = $template->code_expiration_minutes ?? 10;
                $this->otpService->store($phone, $otpCode, $expiryMinutes);
            }

            // Build template data for sending
            // Authentication templates require both body and button components with the OTP
            $templateData = [
                'phone_number' => $phone,
                'template_name' => $template->template_name,
                'language' => $template->language,
                'template_id' => $template->template_id,
                'category' => 'AUTHENTICATION',
                'rel_type' => 'otp',
                'rel_id' => 0,
                'tenant_id' => $tenantId,
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otpCode,
                            ],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otpCode,
                            ],
                        ],
                    ],
                ],
            ];

            // Set WhatsApp tenant context
            $this->setWaTenantId($tenantId);

            // Send via WhatsApp
            $result = $this->sendTemplateWithComponents(
                $phone,
                $template->template_name,
                $template->language,
                $templateData['components']
            );

            if (! $result['status']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send OTP',
                    'error_details' => $result,
                ], 400);
            }

            // Increment rate limit counter
            $this->otpService->incrementRateLimit($phone);

            // Log activity
            whatsapp_log('OTP sent successfully', 'info', [
                'phone' => $phone,
                'template' => $template->template_name,
                'otp_length' => strlen($otpCode),
                'message_id' => $result['data']['messages'][0]['id'] ?? null,
            ], null, $tenantId);

            $response = [
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'message_id' => $result['data']['messages'][0]['id'] ?? null,
                    'phone' => $phone,
                    'template' => $template->template_name,
                    'expires_at' => $storeForVerification ? now()->addMinutes($template->code_expiration_minutes ?? 10)->toIso8601String() : null,
                    'rate_limit' => [
                        'remaining_hour' => $rateLimit['remaining_hour'] - 1,
                        'remaining_day' => $rateLimit['remaining_day'] - 1,
                    ],
                ],
            ];

            // Include OTP code in response only if not storing for verification
            if (! $storeForVerification) {
                $response['data']['otp_code'] = $otpCode;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            whatsapp_log('OTP send error', 'error', [
                'phone' => $request->input('phone'),
                'error' => $e->getMessage(),
            ], $e, tenant_id());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verify OTP code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $code = $request->input('otp_code');

        $result = $this->otpService->verify($phone, $code);

        return response()->json([
            'success' => $result['status'],
            'message' => $result['message'],
            'attempts_remaining' => $result['attempts_remaining'] ?? null,
        ], $result['status'] ? 200 : 400);
    }

    /**
     * Send template with components helper
     */
    protected function sendTemplateWithComponents(
        string $phone,
        string $templateName,
        string $language,
        array $components
    ): array {
        $whatsappApi = $this->loadConfig();

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language,
                    ],
                    'components' => $components,
                ],
            ];

            $response = $whatsappApi->sendMessage($payload);

            return [
                'status' => true,
                'data' => json_decode($response->body(), true),
                'message' => 'Message sent successfully',
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
