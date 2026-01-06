<?php

namespace App\Http\Controllers\PaymentGateways;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    protected $gateway;

    public function __construct()
    {
        $this->gateway = app('billing.manager')->gateway('paystack');
    }

    /**
     * Handle webhook from Paystack.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            // Start timing the webhook processing
            $startTime = microtime(true);

            // Get the payload and signature
            $payload = $request->getContent();
            $signature = $request->header('x-paystack-signature');

            if (! $signature) {

                return response()->json(['error' => 'Missing signature'], 400);
            }

            // Parse the payload
            $webhookData = json_decode($payload, true);

            if (! $webhookData) {

                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            // Extract tenant information from metadata
            $metadata = $webhookData['data']['metadata'] ?? [];
            $tenantId = $metadata['tenant_id'] ?? null;

            if (! $tenantId) {

                return response()->json(['error' => 'No tenant ID found'], 400);
            }

            // Find the tenant
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {

                return response()->json(['error' => 'Tenant not found'], 400);
            }

            // Validate webhook signature using gateway method
            if (! $this->gateway->validateWebhookSignature($payload, $signature)) {

                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Process the webhook event
            $handled = $this->processWebhookEvent($webhookData, $tenant);

            // Calculate processing time
            $processingTime = round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'status' => $handled ? 'success' : 'ignored',
                'processing_time_ms' => $processingTime,
            ]);

        } catch (Exception $e) {
            Log::error('Paystack webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Process webhook event based on event type.
     */
    private function processWebhookEvent(array $webhookData, Tenant $tenant): bool
    {
        $event = $webhookData['event'] ?? '';
        $data = $webhookData['data'] ?? [];

        // Initialize tenant context for data access
        app()->instance('current_tenant', $tenant);

        switch ($event) {
            case 'charge.success':
                return $this->handleChargeSuccess($data, $tenant);

            case 'charge.failed':
                return $this->handleChargeFailed($data, $tenant);

            case 'subscription.create':
                return $this->handleSubscriptionCreate($data, $tenant);

            case 'subscription.disable':
                return $this->handleSubscriptionDisable($data, $tenant);

            case 'invoice.create':
                return $this->handleInvoiceCreate($data, $tenant);

            case 'invoice.payment_failed':
                return $this->handleInvoicePaymentFailed($data, $tenant);

            default:
                return true; // Return true for unhandled events to avoid errors
        }
    }

    /**
     * Handle successful charge event.
     */
    private function handleChargeSuccess(array $data, Tenant $tenant): bool
    {
        $reference = $data['reference'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $invoiceId = $metadata['invoice_id'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;

        if (! $reference || ! $invoiceId) {

            return false;
        }

        try {
            // Find transaction by ID first, then by idempotency_key
            $transaction = null;
            if ($transactionId) {
                $transaction = \App\Models\Transaction::find($transactionId);
            }

            if (! $transaction) {
                $transaction = \App\Models\Transaction::where('idempotency_key', $reference)
                    ->whereHas('invoice', function ($query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id);
                    })
                    ->first();
            }

            if (! $transaction) {

                return false;
            }

            // Update transaction status and metadata
            $transaction->update([
                'status' => \App\Models\Transaction::STATUS_SUCCESS,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paystack_response' => $data,
                    'webhook_processed_at' => now()->toISOString(),
                ]),
            ]);

            // Apply any remaining credit
            $remainingCredit = $metadata['remaining_credit'] ?? 0;
            if ($remainingCredit > 0) {
                $transaction->invoice->tenant->deductCreditBalance($remainingCredit);
            }

            // Fire transaction created event
            event(new \App\Events\TransactionCreated($transaction->id, $transaction->invoice_id));

            return true;
        } catch (Exception $e) {
            Log::error('Paystack webhook: Failed to process charge success', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
            ]);

            return false;
        }
    }

    /**
     * Handle failed charge event.
     */
    private function handleChargeFailed(array $data, Tenant $tenant): bool
    {
        $reference = $data['reference'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $invoiceId = $metadata['invoice_id'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;

        if (! $reference || ! $invoiceId) {

            return false;
        }

        try {
            // Find transaction by ID first, then by idempotency_key
            $transaction = null;
            if ($transactionId) {
                $transaction = \App\Models\Transaction::find($transactionId);
            }

            if (! $transaction) {
                $transaction = \App\Models\Transaction::where('idempotency_key', $reference)
                    ->whereHas('invoice', function ($query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id);
                    })
                    ->first();
            }

            if (! $transaction) {

                return false;
            }

            // Update transaction status
            $transaction->update([
                'status' => \App\Models\Transaction::STATUS_FAILED,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paystack_response' => $data,
                    'failure_reason' => $data['gateway_response'] ?? 'Payment failed',
                    'webhook_processed_at' => now()->toISOString(),
                ]),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Paystack webhook: Failed to process charge failure', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
            ]);

            return false;
        }
    }

    /**
     * Handle subscription creation event.
     */
    private function handleSubscriptionCreate(array $data, Tenant $tenant): bool
    {
        return true;
    }

    /**
     * Handle subscription disable event.
     */
    private function handleSubscriptionDisable(array $data, Tenant $tenant): bool
    {
        return true;
    }

    /**
     * Handle invoice creation event.
     */
    private function handleInvoiceCreate(array $data, Tenant $tenant): bool
    {
        return true;
    }

    /**
     * Handle invoice payment failure event.
     */
    private function handleInvoicePaymentFailed(array $data, Tenant $tenant): bool
    {
        return true;
    }
}
