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
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (
                Schema::hasTable($tableName) &&
                Schema::hasColumn($tableName, 'type')
            ) {
                DB::statement("
                    ALTER TABLE `$tableName`
                    MODIFY `type` ENUM('lead','customer','guest')
                    NOT NULL
                ");
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

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (
                Schema::hasTable($tableName) &&
                Schema::hasColumn($tableName, 'type')
            ) {
                DB::statement("
                    ALTER TABLE `$tableName`
                    MODIFY `type` ENUM('lead','customer')
                    NOT NULL
                ");
            }
        }
    }
};
