<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('campaign_details')) {
            Schema::table('campaign_details', function (Blueprint $table) {
                // Optimize campaign detail lookups
                $table->index(['campaign_id', 'tenant_id', 'status'], 'idx_campaign_processing');
                $table->index(['status', 'message_status'], 'idx_status_lookup');
            });
        }

        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                // Optimize campaign scheduling queries
                $table->index(['is_sent', 'scheduled_send_time', 'pause_campaign'], 'idx_campaign_scheduling');
                $table->index(['tenant_id', 'is_sent'], 'idx_tenant_campaigns');
            });
        }

        // Optimize queue processing (if using database queue)
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index(['queue', 'available_at'], 'idx_queue_processing');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('campaign_details')) {
            Schema::table('campaign_details', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_details', 'campaign_id')) {
                    $table->dropIndex('idx_campaign_processing');
                }

                if (Schema::hasColumn('campaign_details', 'status')) {
                    $table->dropIndex('idx_status_lookup');
                }
            });
        }

        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                // drop if exists; dropIndex will throw if missing so only attempt when table exists
                $table->dropIndex('idx_campaign_scheduling');
                $table->dropIndex('idx_tenant_campaigns');
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('idx_queue_processing');
            });
        }
    }
};
