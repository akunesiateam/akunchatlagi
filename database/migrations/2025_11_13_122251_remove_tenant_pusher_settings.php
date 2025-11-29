<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes tenant-specific pusher settings from the database since
     * Pusher is now managed globally by the super admin.
     */
    public function up(): void
    {
        // Remove pusher settings from tenant settings table
        DB::table('tenant_settings')->where('group', 'pusher')->delete();

        // Log the migration for admin awareness
        \Log::info('Removed tenant-specific pusher settings. Pusher is now managed globally by super admin.');
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration is not easily reversible as it removes data.
     * If rollback is needed, tenant pusher settings would need to be
     * manually reconfigured by each tenant.
     */
    public function down(): void
    {
        // Cannot easily restore deleted settings data
        // Tenants would need to reconfigure their pusher settings manually
        \Log::warning('Migration rollback: Tenant pusher settings were not restored. Manual reconfiguration required.');
    }
};
