<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the email template type from 'tenant' to 'admin' for ticket-status-changed
        EmailTemplate::where('slug', 'ticket-status-changed')
            ->update(['type' => 'admin']);
        EmailTemplate::where('slug', 'ticket-reply-tenant')
            ->update(['type' => 'admin']);
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
