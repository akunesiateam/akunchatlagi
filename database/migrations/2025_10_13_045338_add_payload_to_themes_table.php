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
        if (Schema::hasTable('themes')) {
            Schema::table('themes', function (Blueprint $table) {
                if (! Schema::hasColumn('themes', 'payload')) {
                    $table->longText('payload')->nullable();
                }

                if (! Schema::hasColumn('themes', 'theme_html')) {
                    $table->longText('theme_html')->nullable();
                }

                if (! Schema::hasColumn('themes', 'theme_css')) {
                    $table->longText('theme_css')->nullable();
                }

                if (! Schema::hasColumn('themes', 'type')) {
                    $table->enum('type', ['core', 'custom'])->default('core');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('themes')) {
            Schema::table('themes', function (Blueprint $table) {
                if (Schema::hasColumn('themes', 'payload')) {
                    $table->dropColumn('payload');
                }
                if (Schema::hasColumn('themes', 'theme_html')) {
                    $table->dropColumn('theme_html');
                }
                if (Schema::hasColumn('themes', 'theme_css')) {
                    $table->dropColumn('theme_css');
                }

                if (Schema::hasColumn('themes', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }
    }
};
