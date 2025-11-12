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
        if (! Schema::hasTable('plan_features')) {
            // Create plan_features pivot table
            Schema::create('plan_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')
                    ->constrained('plans')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('feature_id')->nullable();

                // Metadata columns
                $table->string('name')->nullable()
                    ->comment('Feature display name');
                $table->string('slug')->nullable()
                    ->comment('URL-friendly feature name');
                $table->text('description')->nullable()
                    ->comment('Feature description');

                // Value and reset mechanism
                $table->string('value')->default('0')
                    ->comment('Feature value or limit');
                $table->integer('resettable_period')->nullable()
                    ->comment('Period after which usage resets');
                $table->string('resettable_interval')->nullable()
                    ->comment('Interval for reset (day, month, year)');

                $table->timestamps();

                // Unique constraint and performance indexes
                $table->index(['plan_id']);
                $table->foreign('feature_id')->references('id')->on('features')->onDelete('set null');
                $table->index(['feature_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
