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
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Only add columns if they don't exist
                if (! Schema::hasColumn('subscriptions', 'metadata')) {
                    $table->text('metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Only add columns if they don't exist
                if (Schema::hasColumn('subscriptions', 'metadata')) {
                    $table->dropColumn('metadata');
                }
            });
        }
    }
};
