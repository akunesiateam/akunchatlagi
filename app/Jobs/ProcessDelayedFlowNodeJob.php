<?php

namespace App\Jobs;

use App\Http\Controllers\Whatsapp\WhatsAppWebhookController;
use App\Traits\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDelayedFlowNodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsApp;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public array $backoff = [10, 30, 60];

    /**
     * The maximum number of seconds the job should be allowed to run
     */
    public int $timeout = 120;

    /**
     * Maximum number of exceptions before failing
     */
    public int $maxExceptions = 3;

    /**
     * Delete the job if models no longer exist
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * The nodes to process after delay
     */
    protected array $nextNodes;

    /**
     * The complete flow data
     */
    protected array $flowData;

    /**
     * Contact data for variable replacement
     */
    protected array $contactData;

    /**
     * Trigger message
     */
    protected string $triggerMsg;

    /**
     * Chat ID
     */
    protected int $chatId;

    /**
     * Contact number
     */
    protected string $contactNumber;

    /**
     * Phone number ID
     */
    protected string $phoneNumberId;

    /**
     * Context data
     */
    protected array $context;

    /**
     * Already processed node IDs
     */
    protected array $processedNodeIds;

    /**
     * Tenant ID for multi-tenancy
     */
    protected int $tenantId;

    /**
     * Tenant subdomain for multi-tenancy
     */
    protected string $tenantSubdomain;

    /**
     * Create a new job instance
     */
    public function __construct(
        array $nextNodes,
        array $flowData,
        $contactData, // Accept both array and Contact model
        string $triggerMsg,
        int $chatId,
        string $contactNumber,
        string $phoneNumberId,
        array $context,
        array $processedNodeIds,
        int $tenantId,
        string $tenantSubdomain
    ) {
        $this->nextNodes = $nextNodes;
        $this->flowData = $flowData;
        // Convert Contact model to array if needed (not stdClass)
        if (is_array($contactData)) {
            $this->contactData = $contactData;
        } elseif (is_object($contactData) && method_exists($contactData, 'toArray')) {
            $this->contactData = $contactData->toArray();
        } else {
            $this->contactData = (array) $contactData;
        }
        $this->triggerMsg = $triggerMsg;
        $this->chatId = $chatId;
        $this->contactNumber = $contactNumber;
        $this->phoneNumberId = $phoneNumberId;
        $this->context = $context;
        $this->processedNodeIds = $processedNodeIds;
        $this->tenantId = $tenantId;
        $this->tenantSubdomain = $tenantSubdomain;

        // Set queue connection and queue name
        $this->onConnection(config('queue.default'));
        $this->onQueue('default');
    }

    /**
     * Execute the job
     *
     * NOTE: Spatie's multitenancy automatically activates the correct tenant
     * before this method is called, using the tenant ID stored in Context
     * when the job was dispatched. This ensures all database operations
     * (like ChatMessage::fromTenant) work correctly with tenant-specific tables.
     */
    public function handle(): void
    {
        try {
            // Spatie has already made the tenant current
            // Now set the webhook controller context for compatibility
            $webhookController = new WhatsAppWebhookController;
            $webhookController->setTenantContext($this->tenantId, $this->tenantSubdomain);

            // Log for debugging
            whatsapp_log('ProcessDelayedFlowNodeJob: Starting execution', 'info', [
                'tenant_id' => $this->tenantId,
                'tenant_subdomain' => $this->tenantSubdomain,
                'chat_id' => $this->chatId,
                'nodes_count' => count($this->nextNodes),
            ]);

            // Process the next nodes sequentially
            // Note: contactData is kept as array for processDelayedNodes
            $result = $webhookController->processDelayedNodes(
                $this->nextNodes,
                $this->flowData,
                $this->contactData,
                $this->triggerMsg,
                $this->chatId,
                $this->contactNumber,
                $this->phoneNumberId,
                $this->context,
                $this->processedNodeIds
            );

            whatsapp_log('ProcessDelayedFlowNodeJob: Completed successfully', 'info', [
                'tenant_id' => $this->tenantId,
                'chat_id' => $this->chatId,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            whatsapp_log('ProcessDelayedFlowNodeJob: Exception occurred', 'error', [
                'job_id' => $this->job?->getJobId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'contact_number' => $this->contactNumber,
                'chat_id' => $this->chatId,
                'tenant_id' => $this->tenantId,
            ], $e);

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        whatsapp_log('ProcessDelayedFlowNodeJob: Job failed permanently', 'error', [
            'job_id' => $this->job?->getJobId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'contact_number' => $this->contactNumber,
            'chat_id' => $this->chatId,
            'tenant_id' => $this->tenantId,
            'attempts' => $this->attempts(),
        ], $exception);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'delayed-flow-node',
            'tenant:'.$this->tenantId,
            'chat:'.$this->chatId,
            'contact:'.$this->contactNumber,
        ];
    }
}
