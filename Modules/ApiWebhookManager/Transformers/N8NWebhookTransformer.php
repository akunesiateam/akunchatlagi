<?php

namespace Modules\ApiWebhookManager\Transformers;

use App\Models\Tenant;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Models\Tenant\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class N8NWebhookTransformer
{
    /**
     * Transform model event to N8N-compatible format
     */
    public static function transform(string $event, Model $model, ?array $original = null): array
    {
        $eventType = self::getEventType($event, $model);
        $resourceData = self::getResourceData($model);
        $changes = self::getChanges($model, $original);

        return [
            'event' => [
                'id' => self::generateEventId(),
                'type' => $eventType,
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'tenant' => self::getTenantData(),
            'data' => [
                'resource' => [
                    'type' => self::getResourceType($model),
                    'id' => $model->id,
                    'attributes' => $resourceData['attributes'],
                ],
                'relationships' => $resourceData['relationships'],
            ],
            'changes' => $changes,
            'metadata' => [
                'source' => 'api_webhook_manager',
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
     * Get event type in N8N format (e.g., "contact.created")
     */
    protected static function getEventType(string $event, Model $model): string
    {
        $resourceType = self::getResourceType($model);
        $eventAction = self::mapEventAction($event);

        return "{$resourceType}.{$eventAction}";
    }

    /**
     * Map Laravel event to N8N event action
     */
    protected static function mapEventAction(string $event): string
    {
        return match ($event) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            default => $event,
        };
    }

    /**
     * Get resource type from model
     */
    protected static function getResourceType(Model $model): string
    {
        return match (get_class($model)) {
            Contact::class => 'contact',
            Source::class => 'source',
            Status::class => 'status',
            default => strtolower(class_basename($model)),
        };
    }

    /**
     * Get resource-specific data with attributes and relationships
     */
    protected static function getResourceData(Model $model): array
    {
        $attributes = $model->attributesToArray();
        $relationships = [];

        // Remove sensitive or unnecessary fields
        unset($attributes['tenant_id']);

        if ($model instanceof Contact) {
            $relationships = self::getContactRelationships($model);
            // Remove internal IDs from attributes since we include them in relationships
            unset($attributes['status_id'], $attributes['source_id'], $attributes['assigned_id']);
        } elseif ($model instanceof Source) {
            $relationships = self::getSourceRelationships($model);
        } elseif ($model instanceof Status) {
            $relationships = self::getStatusRelationships($model);
        }

        return [
            'attributes' => $attributes,
            'relationships' => $relationships,
        ];
    }

    /**
     * Get Contact relationships for N8N
     */
    protected static function getContactRelationships(Contact $model): array
    {
        $relationships = [];

        // Status relationship
        if ($model->relationLoaded('status') && $model->status) {
            $relationships['status'] = [
                'id' => $model->status->id,
                'name' => $model->status->name,
                'color' => $model->status->color ?? null,
            ];
        }

        // Source relationship
        if ($model->relationLoaded('source') && $model->source) {
            $relationships['source'] = [
                'id' => $model->source->id,
                'name' => $model->source->name,
            ];
        }

        // Groups relationship (multiple)
        $groupIds = $model->getGroupIds();
        if (! empty($groupIds)) {
            $groups = Group::whereIn('id', $groupIds)->get();
            $relationships['groups'] = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                ];
            })->values()->all();
        }

        // Assigned user relationship
        if ($model->relationLoaded('user') && $model->user) {
            $relationships['assigned_to'] = [
                'id' => $model->user->id,
                'name' => $model->user->name,
                'email' => $model->user->email,
            ];
        }

        return $relationships;
    }

    /**
     * Get Source relationships for N8N
     */
    protected static function getSourceRelationships(Source $model): array
    {
        $relationships = [];

        // Contacts count
        if ($model->relationLoaded('contacts')) {
            $relationships['contacts_count'] = $model->contacts->count();
        }

        return $relationships;
    }

    /**
     * Get Status relationships for N8N
     */
    protected static function getStatusRelationships(Status $model): array
    {
        $relationships = [];

        // Contacts count
        if ($model->relationLoaded('contacts')) {
            $relationships['contacts_count'] = $model->contacts->count();
        }

        return $relationships;
    }

    /**
     * Get tenant data
     */
    protected static function getTenantData(): array
    {
        $tenantId = tenant_id();

        if (! $tenantId) {
            return [
                'id' => null,
                'name' => null,
                'domain' => null,
            ];
        }

        $tenant = Tenant::find($tenantId);

        return [
            'id' => $tenant->id ?? null,
            'name' => $tenant->company_name ?? null,
            'domain' => $tenant->domain ?? tenant_subdomain(),
        ];
    }

    /**
     * Get changes between original and current values
     */
    protected static function getChanges(Model $model, ?array $original = null): array
    {
        if (is_null($original)) {
            return [
                'previous' => null,
                'current' => $model->attributesToArray(),
                'modified_fields' => null,
            ];
        }

        $current = $model->attributesToArray();
        $modifiedFields = [];

        foreach ($current as $key => $value) {
            if (! array_key_exists($key, $original)) {
                continue;
            }

            if ($original[$key] !== $value) {
                $modifiedFields[] = $key;
            }
        }

        return [
            'previous' => $original,
            'current' => $current,
            'modified_fields' => $modifiedFields,
        ];
    }

    /**
     * Transform incoming N8N webhook to Laravel-compatible format
     */
    public static function fromN8N(array $payload): array
    {
        // Support both N8N format and standard format
        $event = $payload['event']['type'] ?? $payload['event'] ?? 'unknown';
        $data = $payload['data']['resource']['attributes'] ?? $payload['data'] ?? [];
        $metadata = $payload['metadata'] ?? [];

        return [
            'event' => $event,
            'data' => $data,
            'metadata' => $metadata,
            'timestamp' => $payload['event']['timestamp'] ?? $payload['timestamp'] ?? now()->toIso8601String(),
        ];
    }

    /**
     * Get all supported event types for N8N
     */
    public static function getSupportedEventTypes(): array
    {
        return [
            // Contact events
            'contact.created',
            'contact.updated',
            'contact.deleted',

            // Source events
            'source.created',
            'source.updated',
            'source.deleted',

            // Status events
            'status.created',
            'status.updated',
            'status.deleted',
        ];
    }

    /**
     * Validate if event type is supported
     */
    public static function isEventTypeSupported(string $eventType): bool
    {
        return in_array($eventType, self::getSupportedEventTypes());
    }
}
