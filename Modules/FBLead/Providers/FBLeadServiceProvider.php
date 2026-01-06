<?php

namespace Modules\FBLead\Providers;

use App\Models\PlanFeature;
use App\Models\Subscription;
use Corbital\ModuleManager\Classes\ModuleUpdateChecker;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\FBLead\Http\Middleware\FBLeadMiddleware;
use Modules\FBLead\Models\FacebookLead;

class FBLeadServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName = 'FBLead';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerHooks();
        $this->registerLicenseHooks($this->moduleName);
        $this->registerLivewireComponents();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Add CSRF exclusions for Facebook webhook routes
        add_filter('csrf_exclusions', function ($exclusions) {
            $moduleExclusions = [
                'api/webhooks/facebook',
                'api/webhooks/facebook/*',
            ];

            return array_merge($exclusions, $moduleExclusions);
        });
    }

    /**
     * Register translations.
     *
     * @return void
     */
    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/'.strtolower($this->moduleName));

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleName);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleName);
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleName.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleName
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/'.strtolower($this->moduleName));

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        // Register views with both lowercase and original case to ensure compatibility
        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), $this->moduleName);
        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), strtolower($this->moduleName));
    }

    /**
     * Register routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->loadRoutesFrom(module_path($this->moduleName, 'Routes/web.php'));
        $this->loadRoutesFrom(module_path($this->moduleName, 'Routes/api.php'));
    }

    /**
     * Register Livewire components.
     *
     * @return void
     */
    protected function registerLivewireComponents()
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('Modules\FBLead\Livewire\Tenant\Settings\WhatsMark\FacebookLeadSettings', \Modules\FBLead\Livewire\Tenant\Settings\WhatsMark\FacebookLeadSettings::class);
        }
    }

    /**
     * Register middleware for the FBLead module.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('fblead.feature', FBLeadMiddleware::class);
    }

    /**
     * Register hooks for Facebook Lead integration.
     *
     * @return void
     */
    protected function registerHooks()
    {
        // Only register hooks if module is enabled
        if (! $this->isModuleEnabled()) {
            return;
        }

        $tenant_id = tenant_id();
        $data = [];

        if ($tenant_id) {
            $subscription = Subscription::where('tenant_id', $tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();
            if ($subscription) {
                $data = PlanFeature::where('plan_id', $subscription->plan_id)->pluck('slug')->toArray();
            }
        }

        if ($this->isModuleEnabled() && in_array('facebook_lead', $data)) {
            // Add Facebook Lead integration to tenant WhatsApp settings navigation
            add_filter('whatsmark_tenant_settings_navigation', function ($items) {
                // Insert Facebook Lead Integration after WhatsApp Auto Lead
                $newItems = [];
                foreach ($items as $key => $item) {
                    $newItems[$key] = $item;

                    // Add Facebook Lead Integration after whatsapp_auto_lead
                    if ($key === 'whatsapp_auto_lead') {
                        $newItems['facebook_lead_integration'] = [
                            'label' => 'facebook_lead_integration',
                            'route' => 'tenant.settings.facebook-lead-integration',
                            'icon' => 'heroicon-o-user-group',
                        ];
                    }
                }

                return $newItems;
            });

            // Add dashboard stats card for Facebook Leads
            add_action('after_dashboard_stats_card', function () {
                $totalLeads = FacebookLead::where('tenant_id', tenant_id())->count();
                echo view('FBLead::dashboard-stats-card', compact('totalLeads'))->render();
            });
        }

        // Register queue worker for Facebook leads processing
        add_action('after_scheduling_tasks_registered', function (Schedule $schedule) {
            $schedule->command('queue:work --queue=facebook-leads --stop-when-empty --sleep=3 --tries=3 --timeout=300 --backoff=30,60,120 --max-time=3600 --max-jobs=50')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }

    /**
     * Register license hooks for the EmbeddedSignup module.
     *
     * @return void
     */
    protected function registerLicenseHooks($module_name)
    {
        add_action('app.middleware.redirect_if_not_installed', function ($request) use ($module_name) {
            if (tenant_check()) {
                $subdomain = tenant_subdomain();
                if ($request->is("{$subdomain}/embedded-signup/*") || $request->is("{$subdomain}/embedded-signup/")) {
                    if (app('module.hooks')->requiresEnvatoValidation($module_name)) {
                        app('module.manager')->deactivate($module_name);

                        return redirect()->to(tenant_route('tenant.dashboard'));
                    }
                }
            }
        });

        add_action('app.middleware.validate_module', function ($request) use ($module_name) {
            if (tenant_check()) {
                $this->validateModuleLicense($request, $module_name);
            }
        });
    }

    protected function validateModuleLicense($request, $module_name)
    {
        $subdomain = tenant_subdomain();
        $updateChecker = new ModuleUpdateChecker;
        if ($request->is("{$subdomain}/settings/facebook-lead-integration/*") || $request->is("{$subdomain}/settings/facebook-lead-integration/")) {
            $result = $updateChecker->validateRequest('60377495');
            if (! $result) {
                app('module.manager')->deactivate($module_name);

                return redirect()->to(tenant_route('tenant.dashboard'));
            }
        }
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return \Corbital\ModuleManager\Facades\ModuleManager::isActive('FBLead');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
