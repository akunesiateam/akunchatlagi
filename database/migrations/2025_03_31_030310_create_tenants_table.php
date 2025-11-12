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
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('company_name')->nullable();
                $table->string('domain')->nullable()->comment('Custom domain if available');
                $table->string('subdomain')->unique()->comment('Subdomain for tenant access');
                $table->text('stripe_customer_id')->nullable();
                $table->enum('status', ['active', 'deactive', 'suspended'])->default('active');
                $table->json('custom_colors')->nullable()->comment('Tenant UI customization colors');
                $table->string('timezone')->nullable()->default('UTC');
                $table->boolean('has_custom_domain')->nullable()->default(false);
                $table->json('features_config')->nullable()->comment('Tenant-specific feature configuration');
                $table->text('address')->nullable();
                $table->integer('country_id')->nullable();
                $table->string('payment_method')->nullable();
                $table->json('payment_details')->nullable();
                $table->string('billing_name')->nullable();
                $table->string('billing_email')->nullable();
                $table->text('billing_address')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_state')->nullable();
                $table->string('billing_zip_code')->nullable();
                $table->string('billing_country')->nullable();
                $table->string('billing_phone')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                // Add indexes for better performance
                $table->index('status');
                $table->index('subdomain');
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
