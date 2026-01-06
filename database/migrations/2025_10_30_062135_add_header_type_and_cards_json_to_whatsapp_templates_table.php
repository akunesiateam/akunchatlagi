<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_templates', 'template_type')) {
                $table->string('template_type', 50)->nullable();
            }

            if (! Schema::hasColumn('whatsapp_templates', 'cards_json')) {
                $table->longText('cards_json')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_templates', 'template_type')) {
                $table->dropColumn('template_type');
            }

            if (Schema::hasColumn('whatsapp_templates', 'cards_json')) {
                $table->dropColumn('cards_json');
            }
        });
    }
};
