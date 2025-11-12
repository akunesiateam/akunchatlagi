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
        Schema::create('facebook_leads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tenant_id');
            $table->string('leadgen_id')->unique();
            $table->string('page_id')->nullable();
            $table->string('form_id')->nullable();
            $table->bigInteger('contact_id')->nullable();
            $table->json('lead_data')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->enum('status', ['processing', 'processed', 'failed'])->default('processing');
            $table->timestamps();

            $table->index(['tenant_id', 'leadgen_id']);
            $table->index(['tenant_id', 'page_id']);
            $table->index(['tenant_id', 'processed_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_leads');
    }
};
