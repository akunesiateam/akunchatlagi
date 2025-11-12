<?php

use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\EmbeddedSignup\Http\Controllers\Tenant\EmbeddedSignupController as TenantEmbeddedSignupController;
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

Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class])->group(
    function () {
        Route::prefix('/{subdomain}')->as('tenant.')->group(function () {

            // Embedded signup routes
            Route::middleware('embedded-signup.token')->prefix('embedded-signup')->name('embedded-signup.')->group(function () {
                Route::get('/embsignin', EmbeddedSignupFlow::class)->name('embsignin');
            });

            // WABA embedded signup callback
            Route::get('/waba/embedded-signup/callback', [TenantEmbeddedSignupController::class, 'callback'])->name('waba.embedded.callback');

            // API routes for availability and config
            Route::get('/api/embedded-signup/availability', [TenantEmbeddedSignupController::class, 'availability'])->name('embedded-signup.availability');
            Route::get('/api/embedded-signup/config', [TenantEmbeddedSignupController::class, 'config'])->name('embedded-signup.config');
        });
    }
);
