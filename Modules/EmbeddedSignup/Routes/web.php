<?php

use App\Http\Middleware\EnsureTenantSecurity;
use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\EmbeddedSignup\Http\Controllers\CoexistenceWebhookController;
use Modules\EmbeddedSignup\Http\Controllers\Tenant\EmbeddedSignupController as TenantEmbeddedSignupController;
use Modules\EmbeddedSignup\Livewire\Tenant\CoexistenceManagement;
use Modules\EmbeddedSignup\Livewire\Tenant\EmbeddedSignupFlow;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the ServiceProvider.
|
*/

Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class, EnsureTenantSecurity::class])->group(
    function () {
        Route::prefix('/{subdomain}')->as('tenant.')->group(function () {

            // Embedded signup routes
            Route::middleware('embedded-signup.token')->prefix('embedded-signup')->name('embedded-signup.')->group(function () {
                Route::get('/embsignin', EmbeddedSignupFlow::class)->name('embsignin');
            });

            // WABA embedded signup callback
            Route::get('/waba/embedded-signup/callback', [TenantEmbeddedSignupController::class, 'callback'])->name('waba.embedded.callback');

            // API route for availability check
            Route::get('/api/embedded-signup/availability', [TenantEmbeddedSignupController::class, 'availability'])->name('embedded-signup.availability');

            // Coexistence management
            Route::get('/coexistence/manage', CoexistenceManagement::class)->name('coexistence.manage');
        });
    }
);

// Coexistence webhook routes (no auth required for webhooks)
Route::post('/webhooks/coexistence', [CoexistenceWebhookController::class, 'handleWebhook'])->name('webhooks.coexistence');
