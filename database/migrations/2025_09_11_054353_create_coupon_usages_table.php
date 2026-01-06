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
        // Check if required tables exist before creating foreign key constraints
        if (! Schema::hasTable('coupons') || ! Schema::hasTable('tenants')) {
            return; // Skip if required tables don't exist
        }

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Add columns without foreign key constraints first
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();

            $table->decimal('discount_amount', 10, 2);
            $table->json('metadata')->nullable(); // Store additional context
            $table->timestamps();

            $table->index(['coupon_id', 'tenant_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['invoice_id']);
            $table->index(['subscription_id']);
        });

        // Add foreign key constraints after table creation if referenced tables exist
        if (Schema::hasTable('invoices')) {
            Schema::table('coupon_usages', function (Blueprint $table) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('coupon_usages', function (Blueprint $table) {
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
