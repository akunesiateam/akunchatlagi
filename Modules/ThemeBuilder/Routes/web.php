<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SanitizeInputs;
use Illuminate\Support\Facades\Route;
use Modules\ThemeBuilder\Http\Controllers\AssetUploadController;
use Modules\ThemeBuilder\Http\Controllers\ThemeCustomizerController;
use Modules\ThemeBuilder\Livewire\Admin\ThemeList;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the ServiceProvider.
|
*/

Route::middleware(['auth', 'web', 'theme.builder', AdminMiddleware::class, SanitizeInputs::class])
    ->prefix('admin') // Prefix the route with 'admin'
    ->name('admin.') // Name the route with 'admin.' prefix
    ->group(function () {
        // With the updated RouteServiceProvider, we can use Livewire components directly with ::class
        Route::get('/theme-list', ThemeList::class)->name('theme.list');
        Route::get('/theme/customize/{theme}', [ThemeCustomizerController::class, 'index'])
            ->name('theme.customize');
        // Load & save payload
        Route::match(['get', 'patch'], '/theme/customize/{theme}/payload', [ThemeCustomizerController::class, 'payload'])
            ->name('theme.customize.payload');

        // Asset upload routes for GrapesJS
        Route::post('/theme/customize/{theme}/assets/upload', [AssetUploadController::class, 'upload'])
            ->name('theme.assets.upload');
        Route::get('/theme/customize/{theme}/assets', [AssetUploadController::class, 'index'])
            ->name('theme.assets.index');
        Route::delete('/theme/customize/{theme}/assets', [AssetUploadController::class, 'delete'])
            ->name('theme.assets.delete');
        // For controller routes, use the full namespace:
        // Route::get('/example', \Modules\LogViewer\Http\Controllers\ExampleController::class . '@index')->name('example.index');
    });
