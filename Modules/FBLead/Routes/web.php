<?php

use App\Http\Middleware\EnsureTenantSecurity;
use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\FBLead\Livewire\Tenant\Settings\WhatsMark\FacebookLeadSettings;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the ServiceProvider.
|
*/

Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class, EnsureTenantSecurity::class, 'fblead.feature'])->group(function () {
    Route::prefix('/{subdomain}')->as('tenant.')->group(function () {
        Route::get('/settings/facebook-lead-integration', FacebookLeadSettings::class)
            ->name('settings.facebook-lead-integration');
    });
});

// Webhook routes are defined in api.php for public access
