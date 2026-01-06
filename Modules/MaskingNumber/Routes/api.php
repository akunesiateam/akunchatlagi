<?php

use Illuminate\Support\Facades\Route;
use Modules\MaskingNumber\Http\Controllers\MaskingNumberController;

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

Route::middleware('api')->prefix('api')->group(function () {
    Route::prefix('MaskingNumber')->group(function () {
        // API routes here
    });
});
