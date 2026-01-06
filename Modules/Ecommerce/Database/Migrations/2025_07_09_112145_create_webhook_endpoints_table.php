<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('webhook_uuid')->unique();
            $table->string('webhook_url', 500);
            $table->string('secret_key')->nullable();
            $table->enum('method', ['GET', 'POST']);
            $table->boolean('is_active')->default(false);

            // Link to existing WhatsApp templates
            $table->unsignedBigInteger('template_id')->nullable();

            // Copied from template_bots table
            $table->text('header_params')->nullable();
            $table->text('body_params')->nullable();
            $table->text('footer_params')->nullable();
            $table->text('filename')->nullable();

            $table->text('phone_extraction_config')->nullable(); // Phone number extraction rules
            $table->json('test_payload')->nullable(); // Sample payload for testing
            $table->integer('sync_start')->nullable()->default(false); // Sync start time

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'webhook_uuid']);
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
