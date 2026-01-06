<?php

namespace App\Jobs;

use App\Models\Tenant\Campaign;
use App\Models\Tenant\CampaignDetail;
use App\Models\Tenant\WhatsappTemplate;
use App\Traits\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsApp;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public array $backoff = [180, 300, 600];

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

    protected int $detailId;

    protected int $campaignId;

    protected int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $detailId,
        int $campaignId,
        int $tenantId
    ) {
        $this->detailId = $detailId;
        $this->campaignId = $campaignId;
        $this->tenantId = $tenantId;

        $this->onQueue('whatsapp-messages');
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping("campaign_detail:{$this->detailId}"),
            new ThrottlesExceptions(10, 5), // Max 10 exceptions per 5 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $isPaused = Cache::remember(
            "campaign:{$this->campaignId}:paused",
            60,
            fn () => Campaign::where('id', $this->campaignId)->value('pause_campaign')
        );

        if ($isPaused) {
            $this->release(180);

            return;
        }

        try {
            // Fetch model
            $detail = CampaignDetail::find($this->detailId);

            // Check existence
            if (! $detail || ! $detail->exists) {
                return;
            }

            // Refresh to get latest data from database
            $detail->refresh();

            // Validate status hasn't changed (might have been processed already)
            if ($detail->status !== 1) {
                return; // Already processed or status changed
            }

            // NOW load relationships after refresh
            $detail->load([
                'campaign',
                'campaign.whatsappTemplate' => function ($query) {
                    $query->where('tenant_id', $this->tenantId);
                },
            ]);

            $campaign = $detail->campaign;
            // $template = $campaign->whatsappTemplate;

            // Format parameters for template
            $template = WhatsappTemplate::where(['template_id' => $campaign->template_id, 'tenant_id' => $this->tenantId])->firstOrFail()->toArray();

            // Format parameters for template
            $template['header_message'] = $template['header_data_text'] ?? null;
            $template['body_message'] = $template['body_data'] ?? null;
            $template['footer_message'] = $template['footer_data'] ?? null;

            if (! $template) {
                $this->fail(new \Exception("Template not found for campaign {$this->campaignId}"));

                return;
            }

            $tenantSubdomain = Cache::remember(
                "tenant:{$this->tenantId}:subdomain",
                3600,
                fn () => tenant_subdomain_by_tenant_id($this->tenantId)
            );

            $contact = \App\Models\Tenant\Contact::fromTenant($tenantSubdomain)
                ->select(['id', 'phone', 'firstname', 'lastname', 'is_opted_out'])
                ->find($detail->rel_id);

            if (! $contact || empty($contact->phone)) {
                $detail->update([
                    'status' => 0,
                    'message_status' => 'failed',
                    'response_message' => 'Contact not found or missing phone number',
                ]);

                return;
            }

            // prevent sending to opted-out contacts
            if ($contact->is_opted_out == 1) {

                $detail->update([
                    'status' => 0,
                    'message_status' => 'failed',
                    'response_message' => 'User has opted-out for campaign',
                ]);

                return;
            }

            // Build message parameters
            $rel_data = array_merge(
                [
                    'rel_type' => $detail->rel_type,
                    'rel_id' => $contact->id,
                    'tenant_id' => $this->tenantId,
                ],
                $template,
                [
                    'campaign_id' => $campaign->id,
                    'template_id' => $campaign->template_id,
                    'filename' => $campaign->filename ?? null,
                    'header_params' => $campaign->header_params,
                    'body_params' => $campaign->body_params,
                    'footer_params' => $campaign->footer_params,
                ]
            );

            $this->setWaTenantId($this->tenantId);

            // Use the WhatsApp trait to send the template
            $response = $this->sendTemplate($contact->phone, $rel_data);

            // Update the detail record with the response
            if (! empty($response['status'])) {
                $detail->update([
                    'status' => 2,
                    'message_status' => 'sent',
                    'whatsapp_id' => $response['data']->messages[0]->id ?? null,
                    'response_message' => null,
                ]);
            } else {
                $this->handleFailedMessage($detail, $response);
            }

            // Clear memory after processing
            DB::connection()->disableQueryLog();
        } catch (Throwable $e) {
            $this->handleFailure($e);

            // Check if we should retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Handle failed message sending.
     */
    protected function handleFailedMessage(CampaignDetail $detail, array $response): void
    {
        $detail->update([
            'status' => 0,
            'message_status' => 'failed',
            'response_message' => $response['message'] ?? 'Unknown error occurred',
        ]);

        if ($this->attempts() < $this->tries) {
            $this->release(180);
        }
    }

    /**
     * Handle job failure.
     */
    protected function handleFailure(Throwable $e): void
    {
        $detail = CampaignDetail::find($this->detailId);

        if ($detail) {
            $detail->update([
                'status' => 0,
                'message_status' => 'failed',
                'response_message' => $e->getMessage(),
            ]);
        }

        $shouldLog = Cache::remember(
            "tenant:{$this->tenantId}:whatsapp_logging",
            3600,
            fn () => (bool) json_decode(get_tenant_setting_by_tenant_id('whatsapp', 'logging', null, $this->tenantId), true)
        );

        if ($shouldLog) {
            whatsapp_log(
                'Campaign message failed',
                'error',
                [
                    'campaign_id' => $this->campaignId,
                    'detail_id' => $this->detailId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                $e
            );
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "campaign_detail:{$this->detailId}";
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return [
            'campaign:'.$this->campaignId,
            'tenant:'.$this->tenantId,
            'whatsapp',
        ];
    }
}
