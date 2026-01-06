<?php

namespace App\Transformers;

use App\Models\Tenant;
use Illuminate\Support\Str;

class WhatsAppWebhookN8NTransformer
{
    /**
     * Transform WhatsApp webhook payload to N8N-compatible format
     */
    public static function transform(array $whatsappPayload, int $tenantId): array
    {
        $eventType = self::detectEventType($whatsappPayload);
        $resourceData = self::extractResourceData($whatsappPayload, $eventType);

        return [
            'event' => [
                'id' => self::generateEventId(),
                'type' => $eventType,
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'tenant' => self::getTenantData($tenantId),
            'data' => [
                'resource' => [
                    'type' => self::getResourceType($eventType),
                    'id' => self::extractResourceId($whatsappPayload),
                    'attributes' => $resourceData['attributes'],
                ],
                'relationships' => $resourceData['relationships'],
            ],
            'whatsapp' => [
                'original_payload' => $whatsappPayload,
            ],
            'metadata' => [
                'source' => 'whatsapp_webhook_forward',
                'environment' => app()->environment(),
                'request_id' => request()->id ?? Str::uuid()->toString(),
            ],
        ];
    }

    /**
     * Generate unique event ID
     */
    protected static function generateEventId(): string
    {
        return 'evt_'.time().'_'.Str::random(10);
    }

    /**
     * Detect event type from WhatsApp webhook payload
     */
    protected static function detectEventType(array $payload): string
    {
        // Extract field from changes
        $field = collect($payload['entry'] ?? [])
            ->flatMap(fn ($entry) => collect($entry['changes'] ?? []))
            ->pluck('field')
            ->first();

        // Determine event type based on field and data structure
        if ($field === 'messages') {
            $messages = collect($payload['entry'] ?? [])
                ->flatMap(fn ($entry) => collect($entry['changes'] ?? []))
                ->flatMap(fn ($change) => collect($change['value']['messages'] ?? []))
                ->first();

            if ($messages) {
                return 'whatsapp.message.received';
            }
        }

        if ($field === 'message_echoes') {
            return 'whatsapp.message.sent';
        }

        // Check for status updates
        $statuses = collect($payload['entry'] ?? [])
            ->flatMap(fn ($entry) => collect($entry['changes'] ?? []))
            ->flatMap(fn ($change) => collect($change['value']['statuses'] ?? []))
            ->first();

        if ($statuses) {
            $status = $statuses['status'] ?? null;

            return match ($status) {
                'delivered' => 'whatsapp.message.delivered',
                'read' => 'whatsapp.message.read',
                'sent' => 'whatsapp.message.sent',
                'failed' => 'whatsapp.message.failed',
                default => 'whatsapp.status.updated',
            };
        }

        // Map other field types to event types
        return match ($field) {
            'account_alerts' => 'whatsapp.account.alert',
            'account_review_update' => 'whatsapp.account.review_updated',
            'account_update' => 'whatsapp.account.updated',
            'business_capability_update' => 'whatsapp.business.capability_updated',
            'business_status_update' => 'whatsapp.business.status_updated',
            'calls' => 'whatsapp.call.received',
            'flows' => 'whatsapp.flow.event',
            'message_template_status_update' => 'whatsapp.template.status_updated',
            'message_template_quality_update' => 'whatsapp.template.quality_updated',
            'phone_number_quality_update' => 'whatsapp.phone.quality_updated',
            'phone_number_name_update' => 'whatsapp.phone.name_updated',
            default => "whatsapp.{$field}",
        };
    }

    /**
     * Get resource type from event type
     */
    protected static function getResourceType(string $eventType): string
    {
        $parts = explode('.', $eventType);

        return $parts[1] ?? 'unknown';
    }

    /**
     * Extract resource ID from WhatsApp payload
     */
    protected static function extractResourceId(array $payload): ?string
    {
        // Try to extract message ID
        $messageId = collect($payload['entry'] ?? [])
            ->flatMap(fn ($entry) => collect($entry['changes'] ?? []))
            ->flatMap(fn ($change) => collect($change['value']['messages'] ?? []))
            ->pluck('id')
            ->first();

        if ($messageId) {
            return $messageId;
        }

        // Try to extract status ID
        $statusId = collect($payload['entry'] ?? [])
            ->flatMap(fn ($entry) => collect($entry['changes'] ?? []))
            ->flatMap(fn ($change) => collect($change['value']['statuses'] ?? []))
            ->pluck('id')
            ->first();

        if ($statusId) {
            return $statusId;
        }

        // Fallback to entry ID
        return collect($payload['entry'] ?? [])->pluck('id')->first();
    }

    /**
     * Extract resource data based on event type
     */
    protected static function extractResourceData(array $payload, string $eventType): array
    {
        $attributes = [];
        $relationships = [];

        // Extract common data
        $entry = collect($payload['entry'] ?? [])->first();
        $change = collect($entry['changes'] ?? [])->first();
        $value = $change['value'] ?? [];

        // Extract based on resource type
        if (str_contains($eventType, 'message')) {
            $message = collect($value['messages'] ?? [])->first();
            $status = collect($value['statuses'] ?? [])->first();

            if ($message) {
                $attributes = [
                    'message_id' => $message['id'] ?? null,
                    'from' => $message['from'] ?? null,
                    'timestamp' => $message['timestamp'] ?? null,
                    'type' => $message['type'] ?? null,
                    'text' => $message['text']['body'] ?? null,
                    'context' => $message['context'] ?? null,
                ];

                // Extract contact info
                $contacts = collect($value['contacts'] ?? [])->first();
                if ($contacts) {
                    $relationships['contact'] = [
                        'wa_id' => $contacts['wa_id'] ?? null,
                        'name' => $contacts['profile']['name'] ?? null,
                    ];
                }

                // Extract metadata
                $relationships['metadata'] = [
                    'phone_number_id' => $value['metadata']['phone_number_id'] ?? null,
                    'display_phone_number' => $value['metadata']['display_phone_number'] ?? null,
                ];
            } elseif ($status) {
                $attributes = [
                    'message_id' => $status['id'] ?? null,
                    'status' => $status['status'] ?? null,
                    'timestamp' => $status['timestamp'] ?? null,
                    'recipient_id' => $status['recipient_id'] ?? null,
                    'conversation' => $status['conversation'] ?? null,
                    'pricing' => $status['pricing'] ?? null,
                    'errors' => $status['errors'] ?? null,
                ];

                // Extract metadata
                $relationships['metadata'] = [
                    'phone_number_id' => $value['metadata']['phone_number_id'] ?? null,
                    'display_phone_number' => $value['metadata']['display_phone_number'] ?? null,
                ];
            }
        } elseif (str_contains($eventType, 'account')) {
            $attributes = [
                'field' => $change['field'] ?? null,
                'value' => $value,
            ];
        } elseif (str_contains($eventType, 'business')) {
            $attributes = [
                'field' => $change['field'] ?? null,
                'value' => $value,
            ];
        } elseif (str_contains($eventType, 'template')) {
            $attributes = [
                'field' => $change['field'] ?? null,
                'message_template_id' => $value['message_template_id'] ?? null,
                'message_template_name' => $value['message_template_name'] ?? null,
                'message_template_language' => $value['message_template_language'] ?? null,
                'event' => $value['event'] ?? null,
            ];
        } else {
            // Generic attributes for other event types
            $attributes = [
                'field' => $change['field'] ?? null,
                'value' => $value,
            ];
        }

        return [
            'attributes' => $attributes,
            'relationships' => $relationships,
        ];
    }

    /**
     * Get tenant data
     */
    protected static function getTenantData(int $tenantId): array
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return [
                'id' => $tenantId,
                'name' => null,
                'domain' => null,
            ];
        }

        return [
            'id' => $tenant->id,
            'name' => $tenant->company_name ?? null,
        ];
    }

    /**
     * Get all supported event types for WhatsApp webhooks
     */
    public static function getSupportedEventTypes(): array
    {
        return [
            // Message events
            'whatsapp.message.received',
            'whatsapp.message.sent',
            'whatsapp.message.delivered',
            'whatsapp.message.read',
            'whatsapp.message.failed',

            // Status events
            'whatsapp.status.updated',

            // Account events
            'whatsapp.account.alert',
            'whatsapp.account.review_updated',
            'whatsapp.account.updated',

            // Business events
            'whatsapp.business.capability_updated',
            'whatsapp.business.status_updated',

            // Template events
            'whatsapp.template.status_updated',
            'whatsapp.template.quality_updated',

            // Phone events
            'whatsapp.phone.quality_updated',
            'whatsapp.phone.name_updated',

            // Other events
            'whatsapp.call.received',
            'whatsapp.flow.event',
        ];
    }

    /**
     * Validate if event type is supported
     */
    public static function isEventTypeSupported(string $eventType): bool
    {
        return in_array($eventType, self::getSupportedEventTypes()) || str_starts_with($eventType, 'whatsapp.');
    }
}
