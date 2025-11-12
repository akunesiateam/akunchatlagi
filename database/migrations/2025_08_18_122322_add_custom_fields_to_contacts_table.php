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
        // If tenants table (or model) isn't available, skip migration
        if (! Schema::hasTable('tenants')) {
            return;
        }

        // Get all tenant table names
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->subdomain.'_contacts';

            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (! Schema::hasColumn($table->getTable(), 'custom_fields_data')) {
                        $table->json('custom_fields_data')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            $tableName = $tenant->subdomain.'_contacts';

            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'custom_fields_data')) {
                        $table->dropColumn('custom_fields_data');
                    }
                });
            }
        }
    }
};
