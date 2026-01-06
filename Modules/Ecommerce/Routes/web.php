<?php

use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\EcommerceController;
use Modules\Ecommerce\Http\Controllers\TemplateMappingController;
use Modules\Ecommerce\Http\Controllers\WebhookReceiverController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the ServiceProvider.
|
*/

// Tenant-specific routes with authentication
Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class, 'ecommerce.token'])->group(
    function () {
        Route::prefix('/{subdomain}')->as('tenant.')->group(
            function () {
                // Main pages (should come before parameterized routes)
                Route::get('/webhooks', [EcommerceController::class, 'index'])->name('webhooks.index');
                Route::get('/webhook-logs', [EcommerceController::class, 'logs'])->name('webhooks.logs');
                Route::get('/webhook-logs/{logId}', [EcommerceController::class, 'logDetails'])->name('webhooks.logs.details');

                // Parameterized routes (should come after specific routes)
                Route::get('/webhooks/{id}/map-template', [TemplateMappingController::class, 'edit'])->name('webhooks.template-map');
                Route::put('/webhooks/{id}/map-template/update', [TemplateMappingController::class, 'update'])
                    ->name('webhooks.map-template.update');

                // CRUD operations
                Route::post('/webhooks', [EcommerceController::class, 'store'])->name('webhooks.store');
                Route::get('/webhooks/{id}/show', [EcommerceController::class, 'show'])->name('webhooks.show');
                Route::post('/webhooks/{id}/edit', [EcommerceController::class, 'update'])->name('webhooks.update');
                Route::post('/webhooks/{id}/destroy', [EcommerceController::class, 'destroy'])->name('webhooks.destroy');

                Route::get('/webhooks/{id}', [EcommerceController::class, 'show'])->name('webhooks.show');
                Route::post('/webhooks/{id}/start-sync', [EcommerceController::class, 'startSync'])->name('webhooks.startSync');
                // routes/web.php or routes/tenant.php

                Route::post('/webhooks/{id}/payload-destroy', [EcommerceController::class, 'deletePayload'])
                    ->name('webhooks.delete-payload');

                // Additional actions
                Route::post('/webhooks/{id}/toggle-status', [EcommerceController::class, 'toggleStatus'])->name('webhooks.toggle-status');
                Route::get('/webhooks/{id}/logs', [EcommerceController::class, 'getLogs'])->name('webhooks.individual.logs');
                Route::post('/webhooks/{id}/test', [EcommerceController::class, 'testWebhook'])->name('webhooks.test');

                // Statistics and data
                Route::get('/stats', [EcommerceController::class, 'getStats'])->name('stats');
            }
        );
    }
);

// Public API routes for webhook receivers (no auth, no CSRF)
Route::prefix('api')->as('api.')->group(function () {
    // These routes must be outside of web middleware to avoid CSRF protection
    Route::any('/webhooks/{uuid}', [WebhookReceiverController::class, 'receive'])
        ->name('webhooks.receive')
        ->middleware(['api']);

    Route::get('/webhooks/{uuid}/status', [WebhookReceiverController::class, 'status'])
        ->name('webhooks.status')
        ->middleware(['api']);
});
