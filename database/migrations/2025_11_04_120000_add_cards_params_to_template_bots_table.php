<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_bots')) {
            Schema::table('template_bots', function (Blueprint $table) {
                if (! Schema::hasColumn('template_bots', 'cards_params')) {
                    $table->json('cards_params')->nullable()->comment('Stores carousel card parameters for template bots');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('template_bots')) {
            Schema::table('template_bots', function (Blueprint $table) {
                if (Schema::hasColumn('template_bots', 'cards_params')) {
                    $table->dropColumn('cards_params');
                }
            });
        }
    }
};
