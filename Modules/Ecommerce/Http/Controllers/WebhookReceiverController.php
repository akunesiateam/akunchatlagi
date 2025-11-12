<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Facades\Tenant;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Jobs\ProcessWebhookJob;
use Modules\Ecommerce\Models\WebhookEndpoints;
use Modules\Ecommerce\Models\WebhookLogs;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class WebhookReceiverController extends Controller
{
    use TenantAware;

    /**
     * Receive webhook payload from external services
     */
    public function receive(Request $request, string $uuid): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Find webhook endpoint by UUID
            $webhook = WebhookEndpoints::where('webhook_uuid', $uuid)
                ->first();

            if (! $webhook) {
                $this->logFailedRequest($request, null, 'Webhook not found', $startTime);

                return response()->json([
                    'error' => 'Webhook not found',
                    'webhook_uuid' => $uuid,
                    'timestamp' => Carbon::now()->toISOString(),
                ], 404);
            }
            if ($webhook && $webhook->sync_start == true) {
                $webhook->test_payload = $this->extractPayload($request);
                $webhook->sync_start = false; // Reset sync start after first payload
                $webhook->save();

                return response()->json([
                    'message' => 'Payload synchronized no further processing',
                    'webhook_uuid' => $uuid,
                    'timestamp' => Carbon::now()->toISOString(),
                ]);
            }

            // Validate HTTP method
            if (strtoupper($request->method()) !== $webhook->method) {
                $this->logFailedRequest($request, $webhook, 'Method not allowed', $startTime);

                return response()->json([
                    'error' => 'Method not allowed',
                    'expected_method' => $webhook->method,
                    'received_method' => $request->method(),
                ], 405);
            }

            // Validate secret key if provided
            if ($webhook->secret_key) {
                $providedSecret = $request->header('X-Webhook-Secret') ??
                    $request->header('Authorization') ??
                    $request->input('secret');

                if (! $this->validateSecret($providedSecret, $webhook->secret_key)) {
                    $this->logFailedRequest($request, $webhook, 'Invalid secret key', $startTime);

                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'Invalid or missing secret key',
                    ], 401);
                }
            }

            // Get payload based on content type
            $payload = $this->extractPayload($request);

            // Extract phone number from payload
            $phoneNumber = $this->extractPhoneNumber($webhook, $payload);

            if (! $phoneNumber && $webhook->phone_extraction_config) {
                $this->logFailedRequest($request, $webhook, 'Phone number not found in payload', $startTime);

                return response()->json([
                    'error' => 'Phone number not found in payload',
                    'debug' => [
                        'extraction_config' => $webhook->phone_extraction_config,
                        'payload_keys' => array_keys($payload),
                    ],
                ], 422);
            }

            // Log the successful request
            $log = $this->logSuccessfulRequest($request, $webhook, $payload, $phoneNumber, $startTime);

            // Dispatch job for async processing
            if ($webhook->template_id && $phoneNumber) {
                if (! Tenant::checkCurrent()) {
                    // Try to set tenant context manually if possible
                    if ($webhook->tenant_id) {
                        $tenant = \App\Models\Tenant::find($webhook->tenant_id);
                        if ($tenant) {
                            $tenant->makeCurrent();
                        }
                    }
                }

                ProcessWebhookJob::dispatch($webhook, $payload, $phoneNumber, $log);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully',
                'webhook_id' => $webhook->id,
                'log_id' => $log->id,
                'extracted_phone' => $phoneNumber,
                'template_mapped' => ! is_null($webhook->template_id),
                'processing_status' => $webhook->template_id && $phoneNumber ? 'queued' : 'logged_only',
                'timestamp' => Carbon::now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            $this->logFailedRequest($request, null, $e->getMessage(), $startTime);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Failed to process webhook',
                'timestamp' => Carbon::now()->toISOString(),
            ], 500);
        }
    }

    public function extractPhoneNumber(WebhookEndpoints $webhook, array $payload): ?string
    {
        $config = $webhook->phone_extraction_config;

        if (empty($config)) {
            return null;
        }

        // Normalize: turn "@{data.customer.phone}" → "data.customer.phone"
        $path = preg_replace('/^@{(.+)}$/', '$1', $config);

        // Split path by "."
        $segments = explode('.', $path);

        // Walk the payload
        $value = $payload;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return null; // path not found
            }
        }

        return $value ? preg_replace('/\s+/', '', $value) : null;
    }

    /**
     * Get webhook status (health check endpoint)
     */
    public function status(string $uuid): JsonResponse
    {
        try {
            $webhook = WebhookEndpoints::where('webhook_uuid', $uuid)->first();

            if (! $webhook) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Webhook not found',
                ], 404);
            }

            $stats = $webhook->getStats();

            return response()->json([
                'status' => $webhook->is_active ? 'active' : 'inactive',
                'webhook' => [
                    'name' => $webhook->name,
                    'method' => $webhook->method,
                    'created_at' => $webhook->created_at,
                    'has_secret' => $webhook->hasSecretKey(),
                    'has_template' => ! is_null($webhook->template_id),
                    'can_trigger' => $webhook->canBeTrigger(),
                ],
                'stats' => $stats,
                'last_check' => Carbon::now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract payload from request based on content type
     */
    private function extractPayload(Request $request): array
    {
        $contentType = $request->header('Content-Type', '');

        if (strpos($contentType, 'application/json') !== false) {
            return $request->json()->all();
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            return $request->all();
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            return $request->all();
        } else {
            // Try to decode JSON from raw body
            $rawBody = $request->getContent();
            $decoded = json_decode($rawBody, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // Fallback to query parameters and form data
            return $request->all();
        }
    }

    /**
     * Validate secret key
     */
    private function validateSecret(?string $providedSecret, string $expectedSecret): bool
    {
        if (! $providedSecret) {
            return false;
        }

        // Remove "Bearer " prefix if present
        $providedSecret = str_replace('Bearer ', '', $providedSecret);

        // Use hash_equals for timing-safe comparison
        return hash_equals($expectedSecret, $providedSecret);
    }

    /**
     * Log successful webhook request
     */
    private function logSuccessfulRequest(
        Request $request,
        WebhookEndpoints $webhook,
        array $payload,
        ?string $phoneNumber,
        float $startTime
    ): WebhookLogs {
        return WebhookLogs::create([
            'tenant_id' => $webhook->tenant_id,
            'webhook_endpoint_id' => $webhook->id,
            'payload' => $payload,
            'extracted_fields' => [
                'phone' => $phoneNumber,
                'extracted_at' => Carbon::now()->toISOString(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ],
            'recipient_phone' => $phoneNumber,
            'meta_template_used' => $webhook->template_id,
            'whatsapp_message_id' => null,
            'send_status' => 'pending',
            'delivery_status' => null,
            'failure_reason' => null,
            'meta_response' => [
                'webhook_received' => true,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toISOString(),
                'payload_size' => strlen(json_encode($payload)),
            ],
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Log failed webhook request
     */
    private function logFailedRequest(
        Request $request,
        ?WebhookEndpoints $webhook,
        string $errorMessage,
        float $startTime
    ): void {
        if ($webhook) {
            WebhookLogs::create([
                'tenant_id' => $webhook->tenant_id,
                'webhook_endpoint_id' => $webhook->id,
                'payload' => $request->all(),
                'extracted_fields' => null,
                'recipient_phone' => null,
                'meta_template_used' => null,
                'whatsapp_message_id' => null,
                'send_status' => 'failed',
                'delivery_status' => null,
                'failure_reason' => $errorMessage,
                'meta_response' => [
                    'error' => $errorMessage,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => Carbon::now()->toISOString(),
                    'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ],
                'processed_at' => Carbon::now(),
            ]);
        }
    }
}
