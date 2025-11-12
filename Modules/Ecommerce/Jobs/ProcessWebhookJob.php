<?php

namespace Modules\Ecommerce\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\WebhookEndpoints;
use Modules\Ecommerce\Models\WebhookLogs;
use Modules\Ecommerce\Traits\EcommerceWebhook;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class ProcessWebhookJob implements ShouldQueue, TenantAware
{
    use Dispatchable, EcommerceWebhook, InteractsWithQueue, Queueable, SerializesModels;

    protected WebhookEndpoints $webhook;

    protected array $payload;

    protected ?string $phoneNumber;

    protected WebhookLogs $log;

    // TenantAware property - required by Spatie
    public $tenant;

    /**
     * Create a new job instance.
     */
    public function __construct(
        WebhookEndpoints $webhook,
        array $payload,
        ?string $phoneNumber,
        WebhookLogs $log
    ) {
        $this->webhook = $webhook;
        $this->payload = $payload;
        $this->phoneNumber = $phoneNumber;
        $this->log = $log;

        // Set tenant for Spatie multitenancy
        $this->tenant = Tenant::current();

        // Set the queue based on tenant configuration
        $this->onQueue('ecommerce_webhook');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if we have all required data
            if (! $this->webhook->template_id || ! $this->phoneNumber) {
                $this->markAsFailed('Missing template ID or phone number');

                return;
            }

            if (! $this->webhook->is_active) {
                $this->markAsFailed('Webhook inactive');

                return;
            }

            $extractedFields = $this->extractFields($this->webhook, $this->payload);
            $this->log->update(['recipient_phone' => $this->phoneNumber, 'extracted_fields' => $extractedFields]);

            $template = optional($this->webhook->whatsappTemplate)->toArray();
            if (! $template) {
                $this->markAsFailed('WhatsApp template not configured');

                return;
            }

            $template = array_merge($template, $extractedFields, $this->webhook->toArray());
            $template['rel_type'] = 'webhook';
            $template['relation_id'] = $this->webhook->id;

            $result = $this->sendWebhookTemplateMessage($this->phoneNumber, $template, $this->payload, 'webhook');

            if ($result['status']) {
                $this->markAsSuccess($result);
            } else {
                $this->markAsFailed($result['message'] ?? 'WhatsApp send failed');
            }
        } catch (\Exception $e) {
            Log::error('Webhook job processing failed', [
                'webhook_id' => $this->webhook->id,
                'log_id' => $this->log->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => $this->tenant?->id,
            ]);

            $this->markAsFailed('Processing exception: '.$e->getMessage());
        }
    }

    /**
     * Extract fields from payload based on configured mappings
     */
    private function extractFields(WebhookEndpoints $webhookEndpoint, array $payload): array
    {
        $mappings = $webhookEndpoint->field_mappings ?? [];
        $extractedFields = [];

        foreach ($mappings as $mapping) {
            $sourcePath = $mapping['source_path'] ?? '';
            $templateVariable = $mapping['template_variable'] ?? '';
            $defaultValue = $mapping['default_value'] ?? '';

            if (empty($sourcePath) || empty($templateVariable)) {
                continue;
            }

            $value = data_get($payload, $sourcePath);

            // Use default value if no value found
            if (empty($value) && ! empty($defaultValue)) {
                $value = $defaultValue;
            }

            // Apply transformations if configured
            $value = $this->applyFieldTransformations($value, $mapping);

            $extractedFields[$templateVariable] = $value;
        }

        return $extractedFields;
    }

    /**
     * Apply field transformations (formatting, calculations, etc.)
     */
    private function applyFieldTransformations($value, array $mapping)
    {
        $transformations = $mapping['transformations'] ?? [];

        foreach ($transformations as $transformation) {
            switch ($transformation['type']) {
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'title_case':
                    $value = title_case($value);
                    break;
                case 'format_currency':
                    $value = '$'.number_format($value, 2);
                    break;
                case 'format_date':
                    $format = $transformation['format'] ?? 'Y-m-d';
                    $value = \Carbon\Carbon::parse($value)->format($format);
                    break;
                case 'prefix':
                    $value = $transformation['prefix'].$value;
                    break;
                case 'suffix':
                    $value = $value.$transformation['suffix'];
                    break;
                case 'replace':
                    $value = str_replace($transformation['search'], $transformation['replace'], $value);
                    break;
            }
        }

        return $value;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed completely', [
            'webhook_id' => $this->webhook->id,
            'log_id' => $this->log->id,
            'exception' => $exception->getMessage(),
            'tenant_id' => $this->tenant?->id,
        ]);

        $this->markAsFailed('Job failed: '.$exception->getMessage());
    }

    /**
     * Mark the webhook log as successfully processed
     */
    private function markAsSuccess(array $whatsappResponse): void
    {
        $this->log->update([
            'send_status' => 'sent',
            'whatsapp_message_id' => $whatsappResponse['data']->messages[0]->id ?? null,
            'meta_response' => array_merge($this->log->meta_response ?? [], [
                'whatsapp_triggered' => true,
                'whatsapp_response' => $whatsappResponse,
                'processed_successfully_at' => Carbon::now()->toISOString(),
            ]),
            'status_updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark the webhook log as failed
     */
    private function markAsFailed(string $errorMessage): void
    {
        $this->log->update([
            'send_status' => 'failed',
            'failure_reason' => $errorMessage,
            'meta_response' => array_merge($this->log->meta_response ?? [], [
                'whatsapp_triggered' => false,
                'error_message' => $errorMessage,
                'failed_at' => Carbon::now()->toISOString(),
            ]),
            'status_updated_at' => Carbon::now(),
        ]);
    }
}
