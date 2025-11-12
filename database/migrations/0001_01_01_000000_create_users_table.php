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
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('firstname')->comment('User first name');
                $table->string('lastname')->comment('User last name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->unsignedBigInteger('tenant_id')->nullable()->comment('Tenant ID');
                $table->boolean('is_admin')->default(false)->comment('Whether user is a super admin');
                $table->unsignedBigInteger('role_id')->nullable()->comment('Role ID');
                $table->string('avatar')->nullable()->comment('User profile image');
                $table->string('phone')->nullable()->comment('User phone number');
                $table->string('default_language')->nullable()->comment('User default language');
                $table->integer('country_id')->nullable();
                $table->text('address')->nullable();
                $table->string('user_type')->comment('User Type');
                $table->boolean('active')->default(true)->comment('Whether user is active');
                $table->boolean('send_welcome_mail')->default(false)->comment('Whether send welcome mail.');
                $table->timestamp('last_login_at')->nullable()->comment('Last successful login');
                $table->timestamp('last_password_change')->nullable()->comment('Last password changed');
                $table->rememberToken();
                $table->timestamps();

                $table->index(['is_admin', 'active'], 'idx_users_role_status');
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
