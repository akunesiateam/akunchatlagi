<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if email_templates table exists before updating records
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        try {
            // Update the email template type from 'tenant' to 'admin' for ticket-status-changed
            EmailTemplate::where('slug', 'ticket-status-changed')
                ->update(['type' => 'admin']);
            EmailTemplate::where('slug', 'ticket-reply-tenant')
                ->update(['type' => 'admin']);
        } catch (\Exception $e) {
            // Log error but don't fail the migration
            error_log('Failed to update email templates: '.$e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the email template type back to 'tenant'
        EmailTemplate::where('slug', 'ticket-status-changed')
            ->update(['type' => 'tenant']);
        EmailTemplate::where('slug', 'ticket-reply-tenant')
            ->update(['type' => 'tenant']);
    }
};
