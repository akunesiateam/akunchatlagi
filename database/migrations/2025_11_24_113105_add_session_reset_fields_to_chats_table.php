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
            $tableName = $subdomain.'_chats';

            if (Schema::hasTable($tableName)) {
                // Add session_reset_sent
                if (! Schema::hasColumn($tableName, 'session_reset_sent')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->tinyInteger('session_reset_sent')
                            ->default(0)
                            ->after('bot_stoped_time');
                    });
                }

                // Add session_reset_sent_at
                if (! Schema::hasColumn($tableName, 'session_reset_sent_at')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->timestamp('session_reset_sent_at')
                            ->nullable()
                            ->after('session_reset_sent');
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
            $tableName = $subdomain.'_chats';

            if (Schema::hasTable($tableName)) {

                // Drop session_reset_sent
                if (Schema::hasColumn($tableName, 'session_reset_sent')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('session_reset_sent');
                    });
                }

                // Drop session_reset_sent_at
                if (Schema::hasColumn($tableName, 'session_reset_sent_at')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('session_reset_sent_at');
                    });
                }
            }
        }
    }
};
