<?php

use Illuminate\Support\Facades\Route;
use Modules\FBLead\Http\Controllers\Api\FacebookWebhookController;
use Modules\FBLead\Http\Middleware\ResolveTenantForFacebookWebhook;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the ServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Facebook Webhook routes - accessible without authentication for Meta verification
Route::prefix('webhooks/facebook')->middleware([ResolveTenantForFacebookWebhook::class])->group(function () {
    // Single endpoint for both verification (GET) and lead processing (POST) - like WhatsApp
    Route::match(['get', 'post'], '/{tenant}/facebook-leads', [FacebookWebhookController::class, 'verify'])->name('facebook-webhook.leads');
    // Add OPTIONS support for CORS preflight requests
    Route::options('/{tenant}/facebook-leads', [FacebookWebhookController::class, 'options'])->name('facebook-webhook.leads-options');
});
