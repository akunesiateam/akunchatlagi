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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 2); // Percentage or fixed amount
            $table->integer('usage_limit')->nullable(); // null = unlimited
            $table->integer('usage_count')->default(0);
            $table->integer('usage_limit_per_customer')->nullable();
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->decimal('maximum_discount', 10, 2)->nullable();
            $table->json('applicable_plans')->nullable(); // Plan IDs this coupon applies to
            $table->json('applicable_billing_periods')->nullable(); // ['monthly', 'yearly']
            $table->boolean('first_payment_only')->default(false);
            $table->boolean('is_active')->default(true);

            // Add created_by column without foreign key constraint first
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
            $table->index(['is_active', 'created_at']);
            $table->index(['created_by']);
        });

        // Add foreign key constraint after table creation if users table exists
        if (Schema::hasTable('users')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
