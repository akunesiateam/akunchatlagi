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
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (Schema::hasTable($tableName)) {
                // If is_opted_out column doesn't exist, add it as JSON
                if (! Schema::hasColumn($tableName, 'is_opted_out')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->tinyInteger('is_opted_out')->nullable()->default(0);
                    });
                }
                if (! Schema::hasColumn($tableName, 'opted_out_date')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->timestamp('opted_out_date')->nullable();
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
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $subdomains = DB::table('tenants')->pluck('subdomain');

        foreach ($subdomains as $subdomain) {
            $tableName = $subdomain.'_contacts';

            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['is_opted_out', 'opted_out_date']);
                });
            }
        }
    }
};
