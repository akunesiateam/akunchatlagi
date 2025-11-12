<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add message_id index to existing tenant chat_messages tables
        // This is critical for webhook performance and data accuracy

        $tables = DB::select("SHOW TABLES LIKE '%_chat_messages'");

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            // Check if index doesn't already exist
            $indexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name LIKE '%message_id%'");

            if (empty($indexes)) {
                try {
                    // Add composite index for better performance on message_id + tenant_id queries
                    DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `idx_message_id_tenant_id` (`message_id`, `tenant_id`)");;
                } catch (\Exception $e) {
                    // Index might not exist, that's ok
                }
            }
        }

        // Also add index to campaign_details for whatsapp_id + tenant_id if not exists
        $campaignIndexes = DB::select("SHOW INDEX FROM `campaign_details` WHERE Key_name LIKE '%whatsapp_id%'");

        if (empty($campaignIndexes)) {
            try {
                DB::statement('ALTER TABLE `campaign_details` ADD INDEX `idx_whatsapp_id_tenant_id` (`whatsapp_id`(191), `tenant_id`)');
            } catch (\Exception $e) {
                // Index might not exist, that's ok
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the indexes we added
        $tables = DB::select("SHOW TABLES LIKE '%_chat_messages'");

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            try {
                DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `idx_message_id_tenant_id`");
            } catch (\Exception $e) {
                // Index might not exist, that's ok
            }
        }

        try {
            DB::statement('ALTER TABLE `campaign_details` DROP INDEX `idx_whatsapp_id_tenant_id`');
        } catch (\Exception $e) {
            // Index might not exist, that's ok
        }
    }
};

