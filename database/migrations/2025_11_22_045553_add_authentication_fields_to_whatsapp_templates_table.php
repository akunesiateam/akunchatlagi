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
        if (! Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // Authentication template specific fields
            if (! Schema::hasColumn('whatsapp_templates', 'message_send_ttl_seconds')) {
                $table->integer('message_send_ttl_seconds')->nullable()->after('buttons_data')
                    ->comment('TTL for message delivery in seconds (default 600 for authentication)');
            }

            if (! Schema::hasColumn('whatsapp_templates', 'add_security_recommendation')) {
                $table->boolean('add_security_recommendation')->default(false)->after('message_send_ttl_seconds')
                    ->comment('Include security disclaimer in authentication templates');
            }

            if (! Schema::hasColumn('whatsapp_templates', 'code_expiration_minutes')) {
                $table->integer('code_expiration_minutes')->nullable()->after('add_security_recommendation')
                    ->comment('OTP expiry time in minutes');
            }

            if (! Schema::hasColumn('whatsapp_templates', 'otp_button_config')) {
                $table->json('otp_button_config')->nullable()->after('code_expiration_minutes')
                    ->comment('OTP button configuration (type, otp_type, text, etc.)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_templates', 'message_send_ttl_seconds')) {
                $table->dropColumn('message_send_ttl_seconds');
            }

            if (Schema::hasColumn('whatsapp_templates', 'add_security_recommendation')) {
                $table->dropColumn('add_security_recommendation');
            }

            if (Schema::hasColumn('whatsapp_templates', 'code_expiration_minutes')) {
                $table->dropColumn('code_expiration_minutes');
            }

            if (Schema::hasColumn('whatsapp_templates', 'otp_button_config')) {
                $table->dropColumn('otp_button_config');
            }
        });
    }
};
