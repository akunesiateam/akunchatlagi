<?php

namespace Modules\MaskingNumber\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MaskingNumber\Services\NumberMaskingService;

class MaskingNumberServiceProvider extends ServiceProvider
{
    protected $moduleName = 'MaskingNumber';

    public function boot()
    {
        $this->registerConfig();
        
        // ✅ Wrap with try-catch to prevent early boot errors
        try {
            $this->registerViews();
        } catch (\Exception $e) {
            // Silent fail - views will be registered on next request
        }
        
        $this->manageMenuItem();
    }

    /**
     * Load helper functions from helpers.php
     */
    private function loadHelpers()
    {
        $helperPath = __DIR__ . '/../helpers.php';
        
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    private function manageMenuItem()
    {
        add_filter('tenant_settings_navigation', function ($menu) {
            $menu['masking_number'] = [
                'label' => 'Number Masking',
                'route' => 'tenant.masking-number.settings.view',
                'icon' => 'heroicon-m-eye-slash',
                'condition' => 'module_exists("MaskingNumber") && module_enabled("MaskingNumber")',
            ];

            return $menu;
        });
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            base_path('Modules/'.$this->moduleName.'/Config/config.php'), 
            $this->moduleName
        );
    }

    protected function registerViews()
    {
        $sourcePath = base_path('Modules/'.$this->moduleName.'/resources/views');

        if (is_dir($sourcePath)) {
            $this->loadViewsFrom($sourcePath, $this->moduleName);
        }
    }

    public function register()
    {
        // ✅ Load helpers di register()
        $this->loadHelpers();
        
        // Register RouteServiceProvider
        if (class_exists('Modules\\MaskingNumber\\Providers\\RouteServiceProvider')) {
            $this->app->register(\Modules\MaskingNumber\Providers\RouteServiceProvider::class);
        }
        
        // Register service as singleton
        $this->app->singleton(NumberMaskingService::class, function ($app) {
            return new NumberMaskingService();
        });
    }
}