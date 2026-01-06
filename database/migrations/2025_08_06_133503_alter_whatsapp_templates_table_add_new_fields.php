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
            if (Schema::hasColumn('whatsapp_templates', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->change();
            }

            if (! Schema::hasColumn('whatsapp_templates', 'header_file_url')) {
                $table->text('header_file_url')->nullable();
            }

            if (! Schema::hasColumn('whatsapp_templates', 'header_variable_value')) {
                $table->json('header_variable_value')->nullable();
            }

            if (! Schema::hasColumn('whatsapp_templates', 'body_variable_value')) {
                $table->json('body_variable_value')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_templates', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('whatsapp_templates', 'header_file_url')) {
                $table->dropColumn('header_file_url');
            }

            if (Schema::hasColumn('whatsapp_templates', 'header_variable_value')) {
                $table->dropColumn('header_variable_value');
            }

            if (Schema::hasColumn('whatsapp_templates', 'body_variable_value')) {
                $table->dropColumn('body_variable_value');
            }
        });
    }
};
