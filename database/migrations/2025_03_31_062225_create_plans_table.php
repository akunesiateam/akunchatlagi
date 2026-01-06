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
        if (! Schema::hasTable('plans')) {
            // Create plans table
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();

                // Pricing columns
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('yearly_price', 10, 2)->default(0);
                $table->integer('yearly_discount')->default(0);
                $table->string('billing_period')->default('monthly');
                // Plan flags and metadata
                $table->integer('trial_days')->default(0);
                $table->integer('interval')->default(1);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_free')->default(false);
                $table->boolean('featured')->default(false);
                $table->string('color')->nullable();
                $table->integer('currency_id')->nullable();
                $table->timestamps();

                // Performance-optimized indexes
                $table->index(['is_active', 'slug']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
