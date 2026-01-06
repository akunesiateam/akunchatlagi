<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Rules\PurifiedInput;
use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\WebhookEndpoints;
use Modules\Ecommerce\Models\WebhookLogs;

class EcommerceController extends Controller
{
    protected $featureLimitChecker;

    protected $remainingLimit;

    public function __construct(?FeatureService $featureLimitChecker = null)
    {
        $this->featureLimitChecker = $featureLimitChecker ?? app(FeatureService::class);
        $this->remainingLimit = $this->getRemainingLimitProperty();
    }

    /**
     * Get remaining limit property for compatibility
     */
    private function getRemainingLimitProperty()
    {
        try {
            if ($this->featureLimitChecker) {
                $limit = $this->featureLimitChecker->getRemainingLimit('ecommerce_webhooks');

                return $limit === -1 ? null : $limit;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Display webhook endpoints listing page
     */
    public function index()
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.view'])) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Removed the problematic relationship query that was causing the error
        $webhooks = WebhookEndpoints::where('tenant_id', tenant_id())
            ->orderBy('created_at', 'desc')
            ->get();

        $isUnlimited = $this->remainingLimit === null;
        $remainingLimit = $this->remainingLimit;
        $totalLimit = $this->featureLimitChecker ? $this->featureLimitChecker->getLimit('ecommerce_webhooks') : null;
        $hasReachedLimit = $this->featureLimitChecker->hasReachedLimit('ecommerce_webhooks', WebhookEndpoints::class);

        return view('Ecommerce::index', compact('webhooks', 'isUnlimited', 'remainingLimit', 'totalLimit', 'hasReachedLimit'));
    }

    // EcommerceController.php
    public function show($subdomain, $id)
    {
        $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'webhook' => $webhook,
        ]);
    }

    public function startSync($subdomain, $id)
    {
        $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

        if (is_null($webhook->sync_start) || $webhook->sync_start == 0) {
            $webhook->sync_start = 1;
            $webhook->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sync started',
            'webhook' => $webhook,
        ]);
    }
    // Add this method to your EcommerceController class

    public function deletePayload($subdomain, $id): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.delete'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

            // Clear the test payload
            $webhook->test_payload = null;
            $webhook->is_active = false;
            $webhook->save();

            return response()->json([
                'success' => true,
                'message' => 'Test payload deleted successfully',
                'webhook' => $webhook,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete test payload', [
                'error' => $e->getMessage(),
                'webhook_id' => $id,
                'tenant_id' => tenant_id(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payload: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display webhook logs listing page
     */
    public function logs()
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.view'])) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return view('Ecommerce::logs');
    }

    /**
     * Show webhook log details
     */
    public function logDetails($subdomain, $logId)
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.view'])) {
            return redirect()->to(tenant_route('tenant.dashboard'))->with([
                'status' => 'danger',
                'message' => t('access_denied_note'),
            ]);
        }

        return view('Ecommerce::log-details', ['logId' => $logId]);
    }

    /**
     * Store a new webhook endpoint
     */
    public function store(Request $request): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.create'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200', new PurifiedInput(t('dynamic_input_error'))],
            'description' => ['nullable', 'string', 'max:10000', new PurifiedInput(t('dynamic_input_error'))],
            'method' => ['required', 'in:GET,POST'],
            'secret_key' => ['nullable', 'string', 'max:255', new PurifiedInput(t('dynamic_input_error'))],
            'template_id' => ['nullable', 'exists:whatsapp_templates,id'],
            'phone_extraction_config' => ['nullable', 'string', new PurifiedInput(t('dynamic_input_error'))],
            'test_payload' => ['nullable', 'array'],

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check webhook limit
        if ($this->featureLimitChecker->hasReachedLimit('ecommerce_webhooks', WebhookEndpoints::class)) {
            return response()->json([
                'success' => false,
                'error' => t('ecommerce_webhook_limit_reached_please_upgrade_your_plan'),
            ], 500);
        }

        try {
            $webhookUuid = Str::uuid();
            $webhookUrl = url("api/webhooks/{$webhookUuid}");

            $webhook = WebhookEndpoints::create([
                'tenant_id' => tenant_id(),
                'name' => $request->name,
                'description' => $request->description,
                'webhook_uuid' => $webhookUuid,
                'webhook_url' => $webhookUrl,
                'method' => $request->input('method'),
                'secret_key' => $request->secret_key,
                'template_id' => $request->template_id,
                'phone_extraction_config' => $request->phone_extraction_config,
                'test_payload' => $request->test_payload,
                'is_active' => false,
                'created_by' => Auth::id(),
            ]);

            $this->featureLimitChecker->trackUsage('ecommerce_webhooks');

            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint created successfully',
                'webhook' => $webhook,
                'webhook_url' => $webhookUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook creation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => tenant_id(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create webhook endpoint',
            ], 500);
        }
    }

    /**
     * Update webhook endpoint
     */
    public function update(Request $request, $subdomain, $id): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce.edit'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'method' => 'required|in:GET,POST',
            'secret_key' => 'nullable|string|max:255',
            'phone_extraction_config' => 'nullable|string',
            'test_payload' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

            $webhook->update([
                'name' => $request->name,
                'description' => $request->description,
                'method' => $request->getMethod(),
                'secret_key' => $request->secret_key,
                'template_id' => $request->template_id,
                'phone_extraction_config' => $request->phone_extraction_config,
                'test_payload' => $request->test_payload,
                'is_active' => $request->boolean('is_active', $webhook->is_active),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint updated successfully',
                'webhook' => $webhook,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook update failed', [
                'error' => $e->getMessage(),
                'webhook_id' => $id,
                'tenant_id' => tenant_id(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook endpoint',
            ], 500);
        }
    }

    /**
     * Delete webhook endpoint
     */
    public function destroy($subdomain, $id): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce.delete'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

            // Delete associated logs first
            WebhookLogs::where('webhook_endpoint_id', $id)->delete();

            // Then delete the webhook
            $webhook->delete();

            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook deletion failed', [
                'error' => $e->getMessage(),
                'webhook_id' => $id,
                'tenant_id' => tenant_id(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete webhook endpoint',
            ], 500);
        }
    }

    /**
     * Toggle webhook active status
     */
    public function toggleStatus($subdomain, $id): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce.edit'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);
            $webhook->update(['is_active' => ! $webhook->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook status updated successfully',
                'is_active' => $webhook->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook status',
            ], 500);
        }
    }

    /**
     * Get webhook statistics
     */
    public function getStats(): JsonResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce.view'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $tenantId = tenant_id();

        // Get webhook IDs for this tenant
        $webhookIds = WebhookEndpoints::where('tenant_id', $tenantId)->pluck('id');

        $stats = [
            'total_webhooks' => WebhookEndpoints::where('tenant_id', $tenantId)->count(),
            'active_webhooks' => WebhookEndpoints::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'total_requests_today' => WebhookLogs::whereIn('webhook_endpoint_id', $webhookIds)
                ->whereDate('created_at', today())->count(),
            'successful_requests_today' => WebhookLogs::whereIn('webhook_endpoint_id', $webhookIds)
                ->whereDate('created_at', today())->where('send_status', 'sent')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
