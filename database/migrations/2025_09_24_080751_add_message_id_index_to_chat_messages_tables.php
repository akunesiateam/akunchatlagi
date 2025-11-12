<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add message_id index to existing tenant chat_messages tables
        // This is critical for webhook performance and data accuracy

        try {
            $tables = $this->getChatMessagesTables();

            foreach ($tables as $tableName) {
                // Check if index doesn't already exist
                if (! $this->indexExists($tableName, 'idx_message_id_tenant_id')) {
                    try {
                        // Add composite index for better performance on message_id + tenant_id queries
                        $this->addIndex($tableName, 'idx_message_id_tenant_id', ['message_id', 'tenant_id']);
                    } catch (\Exception $e) {
                    }
                }
            }

            // Also add index to campaign_details for whatsapp_id + tenant_id if not exists
            if (Schema::hasTable('campaign_details')) {
                if (! $this->indexExists('campaign_details', 'idx_whatsapp_id_tenant_id')) {
                    try {
                        $this->addIndex('campaign_details', 'idx_whatsapp_id_tenant_id', ['whatsapp_id', 'tenant_id']);
                    } catch (\Exception $e) {
                    }
                }
            }
        } catch (\Exception $e) {
            // In case of SQLite or testing environment, just skip this migration
        }
    }

    /**
     * Get chat messages tables in a database-agnostic way
     */
    private function getChatMessagesTables(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $tables = DB::select("SHOW TABLES LIKE '%_chat_messages'");

            return array_map(function ($table) {
                return array_values((array) $table)[0];
            }, $tables);
        } elseif ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%_chat_messages'");

            return array_map(function ($table) {
                return $table->name;
            }, $tables);
        } else {
            // For other databases, return empty array to skip
            return [];
        }
    }

    /**
     * Check if index exists in a database-agnostic way
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);

                return ! empty($indexes);
            } elseif ($driver === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('{$tableName}')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }

                return false;
            }
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }

        return false;
    }

    /**
     * Add index in a database-agnostic way
     */
    private function addIndex(string $tableName, string $indexName, array $columns): void
    {
        $driver = DB::getDriverName();
        $columnsString = implode(', ', array_map(function ($col) {
            return "`{$col}`";
        }, $columns));

        if ($driver === 'mysql') {
            // For MySQL, handle varchar length limits
            if (in_array('whatsapp_id', $columns)) {
                $columnsString = str_replace('`whatsapp_id`', '`whatsapp_id`(191)', $columnsString);
            }
            DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` ({$columnsString})");
        } elseif ($driver === 'sqlite') {
            DB::statement("CREATE INDEX IF NOT EXISTS `{$indexName}` ON `{$tableName}` ({$columnsString})");
        } else {
            throw new \Exception("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Remove the indexes we added
            $tables = $this->getChatMessagesTables();

            foreach ($tables as $tableName) {
                try {
                    $this->dropIndex($tableName, 'idx_message_id_tenant_id');
                } catch (\Exception $e) {
                    // Index might not exist, that's ok
                }
            }

            // Check if campaign_details table exists before trying to drop index
            if (Schema::hasTable('campaign_details')) {
                try {
                    $this->dropIndex('campaign_details', 'idx_whatsapp_id_tenant_id');
                } catch (\Exception $e) {
                    // Index might not exist, that's ok
                }
            }
        } catch (\Exception $e) {
            // In case of SQLite or testing environment, just skip this migration rollback
        }
    }

    /**
     * Drop index in a database-agnostic way
     */
    private function dropIndex(string $tableName, string $indexName): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
        } elseif ($driver === 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS `{$indexName}`");
        } else {
            throw new \Exception("Unsupported database driver: {$driver}");
        }
    }
};
