<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhook_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Webhook endpoint reference
            $table->unsignedBigInteger('webhook_endpoint_id');
            $table->foreign('webhook_endpoint_id')->references('id')->on('webhook_endpoints')->onDelete('cascade');

            // Payload and processing data
            $table->json('payload'); // Incoming webhook payload
            $table->json('extracted_fields')->nullable(); // Extracted merge field data

            // WhatsApp message details
            $table->string('recipient_phone', 20)->nullable();
            $table->unsignedBigInteger('meta_template_used')->nullable();

            $table->string('whatsapp_message_id')->nullable(); // Meta message ID

            // Status tracking
            $table->enum('send_status', ['sent', 'failed', 'pending'])->default('pending');
            $table->enum('delivery_status', ['sent', 'delivered', 'read', 'failed'])->nullable();
            $table->text('failure_reason')->nullable();

            // Meta API response data
            $table->json('meta_response')->nullable(); // Full Meta API response

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('webhook_endpoint_id');
            $table->index('recipient_phone');
            $table->index('send_status');
            $table->index('delivery_status');
            $table->index(['webhook_endpoint_id', 'send_status']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_activity_logs');
    }
};
