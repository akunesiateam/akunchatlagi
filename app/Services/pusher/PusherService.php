<?php

namespace App\Services\pusher;

use Pusher\Pusher;

/**
 * Pusher Real-Time Communication Service
 *
 * Manages real-time WebSocket communication for the WhatsApp SaaS multi-tenant
 * application using Pusher Channels. Provides secure, tenant-aware real-time
 * messaging capabilities for chat notifications, status updates, and live features.
 *
 * Key Features:
 * - Global Pusher configuration managed by super admin
 * - Tenant-aware channel and event naming for isolation
 * - Real-time message broadcasting
 * - Batch event processing
 * - Connection resilience and retry logic
 * - Presence channel authentication
 * - Channel information retrieval
 * - Connection health monitoring
 * - Error handling and graceful degradation
 *
 * Real-Time Use Cases:
 * - WhatsApp message notifications
 * - Chat conversation updates
 * - Campaign status broadcasts
 * - User presence indicators
 * - System status notifications
 * - Live dashboard updates
 *
 * The service uses global admin Pusher credentials while ensuring tenant
 * isolation through channel and event naming patterns. This approach
 * centralizes configuration while maintaining security and separation.
 *
 * Usage Example:
 * ```php
 * // Initialize with global settings
 * $pusher = new PusherService();
 *
 * // Trigger tenant-specific notification
 * $result = $pusher->triggerForTenant(
 *     'chat',
 *     'new-message',
 *     ['message' => 'Hello from WhatsApp!', 'sender' => 'Customer'],
 *     123
 * );
 *
 * // Manual tenant-specific channels
 * $result = $pusher->trigger(
 *     $pusher->getTenantChannel('chat', 123),
 *     $pusher->getTenantEvent('new-message', 123),
 *     ['message' => 'Hello!']
 * );
 * ```
 *
 * @author WhatsApp SaaS Team
 *
 * @version 1.0.0
 *
 * @since 1.0.0
 * @see \Pusher\Pusher For underlying Pusher SDK
 * @see tenant_settings_by_group() For tenant configuration
 * @see \App\Http\Controllers\Tenant\ManageChat For chat integration
 */
class PusherService
{
    /**
     * The Pusher client instance.
     *
     * Null when Pusher is not properly configured or initialization fails.
     * Contains the authenticated Pusher client for the current tenant.
     *
     * @var \Pusher\Pusher|null Pusher client instance
     */
    protected ?Pusher $pusher = null;

    /**
     * Number of connection retry attempts.
     *
     * Tracks failed initialization attempts to prevent infinite retry loops
     * and implement exponential backoff for connection failures.
     *
     * @var int Connection retry counter
     */
    protected int $connectionRetries = 0;

    /**
     * Maximum allowed connection retry attempts.
     *
     * Prevents infinite retry loops when Pusher configuration is invalid
     * or Pusher services are unavailable.
     *
     * @var int Maximum retry attempts
     */
    protected const MAX_RETRIES = 3;

    /**
     * Create a new Pusher service instance.
     *
     * Initializes the Pusher client with global admin configuration.
     * Tenant context is maintained separately for channel/event naming.
     *
     * @example
     * ```php
     * // Initialize with global settings
     * $pusher = new PusherService();
     * ```
     */
    public function __construct()
    {
        $this->initializePusher();
    }

    /**
     * Initialize the Pusher client with improved error handling.
     *
     * Retrieves global admin Pusher configuration and creates a new
     * Pusher client instance. Implements retry logic for connection failures
     * and validates all required configuration parameters.
     *
     * @throws \Exception When Pusher initialization fails repeatedly
     *
     * @example
     * ```php
     * // Reinitialize connection
     * $this->initializePusher();
     * ```
     *
     * @see get_settings_by_group() For global configuration retrieval
     */
    protected function initializePusher(): void
    {
        if ($this->connectionRetries >= self::MAX_RETRIES) {
            return;
        }

        try {
            $pusher_settings = get_settings_by_group('pusher');

            // Handle case where settings might be null (not configured yet)
            if (! $pusher_settings) {
                $this->pusher = null;

                return;
            }

            // Get settings with appropriate fallbacks
            $appKey = $pusher_settings->app_key ?? null;
            $appSecret = $pusher_settings->app_secret ?? null;
            $appId = $pusher_settings->app_id ?? null;
            $cluster = $pusher_settings->cluster ?? null;

            // Validate required settings
            if (empty($appKey) || empty($appSecret) || empty($appId) || empty($cluster)) {
                $this->pusher = null;

                return;
            }

            // Initialize Pusher with better options
            $this->pusher = new Pusher(
                $appKey,
                $appSecret,
                $appId,
                [
                    'cluster' => $cluster,
                    'useTLS' => true,
                    'host' => "api-{$cluster}.pusher.com", // Explicitly set the host
                    'port' => 443,
                    'scheme' => 'https',
                    'encrypted' => true,
                    'timeout' => 30,
                    'debug' => config('app.debug', false),
                ]
            );

            $this->connectionRetries = 0;
        } catch (\Exception $e) {
            $this->pusher = null;
            $this->connectionRetries++;
        }
    }

    /**
     * Trigger an event on a specific channel.
     *
     * Broadcasts a real-time event to all subscribers of the specified channel.
     * Returns status information indicating success or failure of the operation.
     * Automatically handles connection issues with retry logic.
     *
     * @param  string  $channel  The channel name to broadcast to
     * @param  string  $event  The event name to trigger
     * @param  array  $data  The data payload to send with the event
     * @return array Status array with 'status' (bool) and 'message' (string)
     *
     * @example
     * ```php
     * // Trigger chat message notification
     * $result = $pusher->trigger(
     *     'tenant-123-chat-room-456',
     *     'new-message',
     *     [
     *         'message_id' => 789,
     *         'sender' => 'Customer',
     *         'content' => 'Hello!',
     *         'timestamp' => now()->toISOString()
     *     ]
     * );
     *
     * if ($result['status']) {
     *     Log::info('Message broadcast successful');
     * } else {
     *     Log::error('Broadcast failed: ' . $result['message']);
     * }
     * ```
     *
     * @see isPusherReady() For connection status checking
     */
    public function trigger(string $channel, string $event, array $data): array
    {
        if (! $this->isPusherReady()) {
            return ['status' => false, 'message' => 'Pusher initialization failed'];
        }

        try {
            // Always pass an array as the 4th parameter
            $this->pusher->trigger($channel, $event, $data, []);

            return ['status' => true, 'message' => 'Pusher connection test successful'];
        } catch (\Exception $e) {

            // Try to reinitialize on connection issues
            if (strpos($e->getMessage(), 'cURL error 28') !== false || strpos($e->getMessage(), 'Connection') !== false || strpos($e->getMessage(), 'Unable to parse URI') !== false) {
                $this->initializePusher();
            }

            return ['status' => false, 'message' => 'Pusher trigger failed: '.$e->getMessage()];
        }
    }

    /**
     * Trigger multiple events simultaneously.
     *
     * Efficiently broadcasts multiple events in a single API call to Pusher.
     * Useful for sending related notifications or updates that should arrive
     * together for better user experience and reduced API overhead.
     *
     * @param  array  $events  Array of event objects with 'channel', 'name', and 'data' keys
     * @return bool True if all events were sent successfully, false otherwise
     *
     * @example
     * ```php
     * $events = [
     *     [
     *         'channel' => 'tenant-123-notifications',
     *         'name' => 'campaign-started',
     *         'data' => ['campaign_id' => 456, 'status' => 'running']
     *     ],
     *     [
     *         'channel' => 'tenant-123-dashboard',
     *         'name' => 'metrics-update',
     *         'data' => ['active_campaigns' => 5, 'messages_sent' => 1200]
     *     ]
     * ];
     *
     * if ($pusher->triggerBatch($events)) {
     *     Log::info('Batch events sent successfully');
     * }
     * ```
     *
     * @see trigger() For single event broadcasting
     */
    public function triggerBatch(array $events): bool
    {
        try {
            if (! $this->isPusherReady()) {
                return false;
            }

            // Pass events to triggerBatch
            $this->pusher->triggerBatch($events);

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Authenticate a user for presence channels.
     *
     * Generates authentication signature for users joining presence channels.
     * Presence channels allow tracking of who is currently subscribed and
     * enable features like "user is typing" indicators and online status.
     *
     * @param  string  $socketId  The client's socket ID from Pusher
     * @param  array  $channelData  User information for presence channel
     * @return string Authentication signature for the presence channel
     *
     * @throws \RuntimeException If Pusher is not properly initialized
     *
     * @example
     * ```php
     * // Authenticate user for chat presence
     * $auth = $pusher->authenticateUser(
     *     $request->input('socket_id'),
     *     [
     *         'user_id' => auth()->id(),
     *         'user_info' => [
     *             'name' => auth()->user()->name,
     *             'avatar' => auth()->user()->avatar_url
     *         ]
     *     ]
     * );
     *
     * return response($auth);
     * ```
     *
     * @see getChannelInfo() For channel statistics
     */
    public function authenticateUser(string $socketId, array $channelData): string
    {
        if (! $this->isPusherReady()) {
            throw new \RuntimeException('Pusher not initialized');
        }

        return $this->pusher->authenticateUser($socketId, $channelData);
    }

    /**
     * Get information about a specific channel.
     *
     * Retrieves channel statistics including subscriber count and presence
     * information. Useful for monitoring channel activity and implementing
     * features based on channel occupancy.
     *
     * @param  string  $channel  The channel name to get information for
     * @return mixed Channel information object from Pusher
     *
     * @throws \RuntimeException If Pusher is not properly initialized
     *
     * @example
     * ```php
     * $info = $pusher->getChannelInfo('tenant-123-chat-room-456');
     * $subscriberCount = $info->subscription_count ?? 0;
     *
     * if ($subscriberCount > 0) {
     *     // Channel has active subscribers
     * }
     * ```
     */
    public function getChannelInfo(string $channel)
    {
        if (! $this->isPusherReady()) {
            throw new \RuntimeException('Pusher not initialized');
        }

        return $this->pusher->getChannelInfo($channel);
    }

    /**
     * Check if the Pusher client is ready for use.
     *
     * Determines whether the Pusher client has been successfully initialized
     * and is available for broadcasting events. Should be called before
     * attempting to use Pusher functionality.
     *
     * @return bool True if Pusher is initialized and ready, false otherwise
     *
     * @example
     * ```php
     * if ($pusher->isPusherReady()) {
     *     $pusher->trigger($channel, $event, $data);
     * } else {
     *     // Fallback to alternative notification method
     *     Mail::send($notificationEmail);
     * }
     * ```
     */
    public function isPusherReady(): bool
    {
        return $this->pusher !== null;
    }

    /**
     * Generate a tenant-specific channel name.
     *
     * Creates a channel name that includes tenant isolation while using
     * global Pusher credentials. This ensures tenant data separation.
     *
     * @param  string  $baseChannel  The base channel name
     * @param  int|string|null  $tenantId  The tenant ID (uses current if null)
     * @return string The tenant-specific channel name
     *
     * @example
     * ```php
     * $channel = $pusher->getTenantChannel('chat', 123);
     * // Returns: 'tenant-123-chat'
     * ```
     */
    public function getTenantChannel(string $baseChannel, $tenantId = null): string
    {
        $tenantId = $tenantId ?? tenant_id();

        return "tenant-{$tenantId}-{$baseChannel}";
    }

    /**
     * Generate a tenant-specific event name.
     *
     * Creates an event name that includes tenant isolation for better
     * organization and potential filtering on the frontend.
     *
     * @param  string  $baseEvent  The base event name
     * @param  int|string|null  $tenantId  The tenant ID (uses current if null)
     * @return string The tenant-specific event name
     *
     * @example
     * ```php
     * $event = $pusher->getTenantEvent('new-message', 123);
     * // Returns: 'tenant-123-new-message'
     * ```
     */
    public function getTenantEvent(string $baseEvent, $tenantId = null): string
    {
        $tenantId = $tenantId ?? tenant_id();

        return "tenant-{$tenantId}-{$baseEvent}";
    }

    /**
     * Trigger an event on a tenant-specific channel.
     *
     * Convenience method that automatically generates tenant-specific
     * channel and event names while using global Pusher credentials.
     *
     * @param  string  $baseChannel  The base channel name
     * @param  string  $baseEvent  The base event name
     * @param  array  $data  The data payload to send
     * @param  int|string|null  $tenantId  The tenant ID (uses current if null)
     * @return array Status array with 'status' (bool) and 'message' (string)
     *
     * @example
     * ```php
     * // Trigger tenant-specific chat notification
     * $result = $pusher->triggerForTenant('chat', 'new-message', [
     *     'message' => 'Hello!',
     *     'sender' => 'Customer'
     * ], 123);
     * ```
     */
    public function triggerForTenant(string $baseChannel, string $baseEvent, array $data, $tenantId = null): array
    {
        $tenantId = $tenantId ?? tenant_id();
        $channel = $this->getTenantChannel($baseChannel, $tenantId);
        $event = $this->getTenantEvent($baseEvent, $tenantId);

        return $this->trigger($channel, $event, $data);
    }

    /**
     * Test the Pusher connection and configuration.
     *
     * Performs a connection test by sending a test event to verify that
     * Pusher credentials are valid and the service is accessible. Useful
     * for configuration validation and health checks.
     *
     * @return array Test result with 'status' (bool), 'message' (string), and optional 'details'
     *
     * @example
     * ```php
     * $test = $pusher->testConnection();
     *
     * if ($test['status']) {
     *     echo "Pusher is working: " . $test['message'];
     * } else {
     *     echo "Pusher failed: " . $test['message'];
     *     if (isset($test['details'])) {
     *         Log::debug('Pusher test details', $test['details']);
     *     }
     * }
     * ```
     *
     * @see isPusherReady() For basic readiness check
     */
    public function testConnection(): array
    {
        if (! $this->isPusherReady()) {
            return [
                'status' => false,
                'message' => 'Pusher not initialized - please check your Pusher configuration',
            ];
        }

        try {
            $result = $this->pusher->trigger('test-channel', 'test-event', ['message' => 'Connection test'], []);

            if (isset($result['status']) && $result['status'] === 200) {
                return [
                    'status' => true,
                    'message' => 'Pusher connection test successful!',
                ];
            }

            return [
                'status' => false,
                'message' => 'Pusher connection test failed',
                'details' => $result,
            ];
        } catch (\Exception $e) {
            // If the error indicates missing or invalid configuration, provide a clearer message
            if (strpos($e->getMessage(), 'Unable to parse URI') !== false) {
                return [
                    'status' => false,
                    'message' => 'Pusher connection failed: Invalid configuration. Please check your Pusher key, secret, app ID, and cluster settings.',
                ];
            }

            return [
                'status' => false,
                'message' => 'Pusher test connection failed: '.$e->getMessage(),
            ];
        }
    }
}
