<?php

namespace Modules\ApiWebhookManager\Enums;

enum WebhookEventType: string
{
    // Contact events
    case CONTACT_CREATED = 'contact.created';
    case CONTACT_UPDATED = 'contact.updated';
    case CONTACT_DELETED = 'contact.deleted';

    // Source events
    case SOURCE_CREATED = 'source.created';
    case SOURCE_UPDATED = 'source.updated';
    case SOURCE_DELETED = 'source.deleted';

    // Status events
    case STATUS_CREATED = 'status.created';
    case STATUS_UPDATED = 'status.updated';
    case STATUS_DELETED = 'status.deleted';

    /**
     * Get all event types as array
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get event types by resource
     */
    public static function forResource(string $resource): array
    {
        return array_filter(self::all(), function ($event) use ($resource) {
            return str_starts_with($event, $resource.'.');
        });
    }

    /**
     * Get resource type from event
     */
    public function getResource(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get action from event
     */
    public function getAction(): string
    {
        return explode('.', $this->value)[1];
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTACT_CREATED => 'Contact Created',
            self::CONTACT_UPDATED => 'Contact Updated',
            self::CONTACT_DELETED => 'Contact Deleted',
            self::SOURCE_CREATED => 'Source Created',
            self::SOURCE_UPDATED => 'Source Updated',
            self::SOURCE_DELETED => 'Source Deleted',
            self::STATUS_CREATED => 'Status Created',
            self::STATUS_UPDATED => 'Status Updated',
            self::STATUS_DELETED => 'Status Deleted',
        };
    }

    /**
     * Check if event is create type
     */
    public function isCreate(): bool
    {
        return $this->getAction() === 'created';
    }

    /**
     * Check if event is update type
     */
    public function isUpdate(): bool
    {
        return $this->getAction() === 'updated';
    }

    /**
     * Check if event is delete type
     */
    public function isDelete(): bool
    {
        return $this->getAction() === 'deleted';
    }
}
