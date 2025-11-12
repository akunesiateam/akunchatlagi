<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Ecommerce\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        // Load web routes - these include both authenticated tenant routes
        // and public API routes for webhooks
        Route::group([
            'middleware' => [], // No default middleware - routes define their own
        ], function () {
            require module_path('Ecommerce', '/Routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        // Only load if api.php exists and has content
        $apiRoutesPath = module_path('Ecommerce', '/Routes/api.php');

        if (file_exists($apiRoutesPath)) {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->moduleNamespace)
                ->group($apiRoutesPath);
        }
    }
}
