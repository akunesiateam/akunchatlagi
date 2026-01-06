<?php

namespace Modules\ApiWebhookManager\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\ApiWebhookManager\Services\WebhookService;
use Modules\ApiWebhookManager\Transformers\N8NWebhookTransformer;

trait HasWebhooks
{
    protected static function bootHasWebhooks()
    {
        static::created(function (Model $model) {
            static::triggerWebhook('created', $model);
        });

        static::updated(function (Model $model) {
            static::triggerWebhook('updated', $model, $model->getOriginal());
        });

        static::deleted(function (Model $model) {
            static::triggerWebhook('deleted', $model);
        });
    }

    protected static function triggerWebhook(string $event, Model $model, ?array $original = null): void
    {
        $webhookSettings = tenant_settings_by_group('webhook');
        if (! isset($webhookSettings['webhook_enabled']) || ! $webhookSettings['webhook_enabled']) {
            return;
        }

        // Get model specific actions from settings based on table name
        $tableActions = static::getWebhookActionsForModel($model, $webhookSettings);
        if (empty($tableActions) || ! static::isEventEnabled($event, $tableActions)) {
            return;
        }

        // Eager load relationships for webhook
        static::loadWebhookRelationships($model);

        // Generate N8N format payload (N8N format only)
        $payload = N8NWebhookTransformer::transform($event, $model, $original);

        $webhookService = app(WebhookService::class);

        $url = $webhookSettings['webhook_url'];
        if (! empty($url)) {
            $webhookService->send(
                $url,
                $payload,
                config('ApiWebhookManager.signing_secret')
            );
        }
    }

    /**
     * Load relationships needed for webhook
     */
    protected static function loadWebhookRelationships(Model $model): void
    {
        $className = get_class($model);

        // Load relationships based on model type (only for Contacts, Sources, Statuses)
        if ($className === 'App\Models\Tenant\Contact') {
            $model->loadMissing(['status', 'source', 'user']);
        }
        // Sources and Statuses don't need additional relationships
    }

    /**
     * Get webhook actions for the current model from settings
     */
    protected static function getWebhookActionsForModel(Model $model, $settings): array
    {
        $tenant_subdomain = tenant_subdomain_by_tenant_id(tenant_id());
        $modelTable = $model->getTable();

        // Only support Contacts, Sources, and Statuses
        $actionMappings = [
            'contacts' => 'contacts_actions',
            'statuses' => 'status_actions',
            'sources' => 'source_actions',
        ];

        $settingKey = $actionMappings[$modelTable] ?? null;
        if (! $settingKey) {
            $modelTable = $model->fromTenant($tenant_subdomain)->getTable();

            return strpos('tenant1_contacts', 'contacts') !== false ? $settings[$actionMappings['contacts']] : [];
        }

        return $settings[$settingKey] ?? [];
    }

    /**
     * Check if an event is enabled in the actions array
     */
    protected static function isEventEnabled(string $event, array $actions): bool
    {
        $eventMappings = [
            'created' => 'create',
            'updated' => 'update',
            'deleted' => 'delete',
        ];

        $actionKey = $eventMappings[$event] ?? null;

        if (! $actionKey) {
            return false;
        }

        return isset($actions[$actionKey]) && $actions[$actionKey] === true;
    }

    /**
     * Get available webhook events for the model
     */
    public static function getWebhookEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    // Ensure incoming timestamps are properly converted
    protected static function convertTimestamp($timestamp, $toFormat = 'Y-m-d H:i:s')
    {
        if (empty($timestamp)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($timestamp)->format($toFormat);
        } catch (\Exception $e) {
            whatsapp_log('Failed to convert timestamp: '.$e->getMessage(), 'error', [
                'timestamp' => $timestamp,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $timestamp;
        }
    }
}
