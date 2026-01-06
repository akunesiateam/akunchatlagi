<?php

use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class, EnsureEmailIsVerified::class])->group(function () {
    Route::prefix('/{subdomain}')->as('tenant.')->group(function () {
        Route::get('/masking-number-settings', '\\Modules\\MaskingNumber\\Livewire\\Tenant\\Settings\\MaskingNumberSettings')->name('masking-number.settings.view');
    });
});