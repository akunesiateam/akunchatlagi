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
        if (! Schema::hasTable('custom_fields')) {
            Schema::create('custom_fields', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('field_name');
                $table->string('field_label');
                $table->enum('field_type', ['text', 'textarea', 'number', 'date', 'dropdown', 'checkbox']);
                $table->json('field_options')->nullable(); // For dropdown options
                $table->string('placeholder')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_required')->default(false);
                $table->string('default_value')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_on_table')->default(false);
                $table->timestamps();

                // Indexes for performance
                $table->index(['tenant_id', 'is_active']);
                $table->index(['tenant_id', 'display_order']);
                $table->unique(['tenant_id', 'field_name']);

                // Foreign key
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
