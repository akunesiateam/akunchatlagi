<?php

namespace App\Traits;

use App\Http\Controllers\Tenant\ManageChat;
use App\Services\pusher\PusherService;

trait ChatNotification
{
    /**
     * Centralized method to trigger chat notifications with enhanced metadata
     * This method should be used across the entire application for consistency
     *
     * @param  int  $chatId  The chat interaction ID
     * @param  int  $messageDbId  The message database ID
     * @param  int  $tenantId  The tenant ID
     * @param  bool  $isIncoming  true for customer messages, false for staff messages
     * @return bool Success status
     */
    public function triggerChatNotification($chatId, $messageDbId, $tenantId = null, $isIncoming = true): bool
    {
        try {
            // Use current tenant ID if not provided
            $tenantId = $tenantId ?? tenant_id();

            // Check if global Pusher is configured
            $pusherSettings = get_settings_by_group('pusher');

            if (
                $pusherSettings &&
                ! empty($pusherSettings->app_key) &&
                ! empty($pusherSettings->app_secret) &&
                ! empty($pusherSettings->app_id) &&
                ! empty($pusherSettings->cluster)
            ) {
                $pusherService = new PusherService;
                $chatData = ManageChat::newChatMessage($chatId, $messageDbId, $tenantId);

                // Add notification metadata directly to the chat data
                $chatData->notification = [
                    'type' => 'new_message',
                    'tenant_id' => $tenantId,
                    'message_id' => $messageDbId,
                    'chat_id' => $chatId,
                    'timestamp' => now()->toISOString(),
                    'is_incoming' => $isIncoming, // true for customer messages, false for staff messages
                ];

                // Use tenant-specific channel and event naming
                $pusherService->triggerForTenant('chat', 'new-message', [
                    'chat' => $chatData,
                ], $tenantId);

                whatsapp_log('Chat notification triggered successfully (trait)', 'debug', [
                    'chat_id' => $chatId,
                    'message_id' => $messageDbId,
                    'tenant_id' => $tenantId,
                    'is_incoming' => $isIncoming,
                ], null, $tenantId);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            whatsapp_log('Error triggering chat notification', 'error', [
                'chat_id' => $chatId,
                'message_db_id' => $messageDbId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], $e, $tenantId);

            return false;
        }
    }

    /**
     * Static version of the notification method for use in static contexts
     *
     * @param  int  $chatId  The chat interaction ID
     * @param  int  $messageDbId  The message database ID
     * @param  int  $tenantId  The tenant ID
     * @param  bool  $isIncoming  true for customer messages, false for staff messages
     * @return bool Success status
     */
    public static function triggerChatNotificationStatic($chatId, $messageDbId, $tenantId, $isIncoming = true): bool
    {
        try {
            // Check if global Pusher is configured
            $pusherSettings = get_settings_by_group('pusher');

            if (
                $pusherSettings &&
                ! empty($pusherSettings->app_key) &&
                ! empty($pusherSettings->app_secret) &&
                ! empty($pusherSettings->app_id) &&
                ! empty($pusherSettings->cluster)
            ) {
                $pusherService = new PusherService;
                $chatData = ManageChat::newChatMessage($chatId, $messageDbId, $tenantId);

                // Use tenant-specific channel and event naming
                $pusherService->triggerForTenant('chat', 'new-message', [
                    'chat' => $chatData,
                    'notification' => [
                        'type' => 'new_message',
                        'tenant_id' => $tenantId,
                        'message_id' => $messageDbId,
                        'chat_id' => $chatId,
                        'timestamp' => now()->toISOString(),
                        'is_incoming' => $isIncoming, // true for customer messages, false for staff messages
                    ],
                ], $tenantId);

                whatsapp_log('Static chat notification triggered successfully', 'debug', [
                    'chat_id' => $chatId,
                    'message_id' => $messageDbId,
                    'tenant_id' => $tenantId,
                    'is_incoming' => $isIncoming,
                ], null, $tenantId);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            whatsapp_log('Error triggering static chat notification', 'error', [
                'chat_id' => $chatId,
                'message_db_id' => $messageDbId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], $e, $tenantId);

            return false;
        }
    }
}
