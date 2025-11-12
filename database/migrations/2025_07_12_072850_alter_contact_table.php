<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure tenants table exists before querying it (database may not be available)
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (Schema::hasTable($tableName)) {
                // If group_id column doesn't exist, add it as JSON
                if (! Schema::hasColumn($tableName, 'group_id')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->json('group_id')->nullable();
                    });
                } else {
                    // If it exists as text, change it to JSON
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->json('group_id')->nullable()->change();
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure tenants table exists before querying it (database may not be available)
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'group_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('group_id')->nullable()->change();
                });
            }
        }
    }
};
