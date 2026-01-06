<?php

namespace Modules\EmbeddedSignup\Providers;

use Corbital\ModuleManager\Classes\ModuleUpdateChecker;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\EmbeddedSignup\Http\Middleware\EmbeddedSignupMiddleware;
use Modules\EmbeddedSignup\Livewire\Tenant\EmbeddedSignupFlow;

class EmbeddedSignupServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName = 'EmbeddedSignup';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(base_path('Modules/'.$this->moduleName.'/database/migrations'));
        $this->registerLivewireComponents();
        $this->registerHooks();
        $this->registerMiddleware();
        $this->registerCommands();
        // Routes are now handled by RouteServiceProvider
        $this->registerLicenseHooks($this->moduleName);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the RouteServiceProvider
        $this->app->register(RouteServiceProvider::class);

        // Register services
        $this->app->singleton(\Modules\EmbeddedSignup\Services\EmbeddedSignupService::class);
        $this->app->singleton(\Modules\EmbeddedSignup\Services\FacebookApiService::class);
        $this->app->singleton(\Modules\EmbeddedSignup\Services\CoexistenceService::class);
    }

    /**
     * Register Livewire components.
     *
     * @return void
     */
    protected function registerLivewireComponents()
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('embedded-signup.tenant.flow', EmbeddedSignupFlow::class);
        }
    }

    /**
     * Register middleware for the EmbeddedSignup module.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('embedded-signup.token', EmbeddedSignupMiddleware::class);
    }

    /**
     * Register module hooks.
     *
     * @return void
     */
    protected function registerHooks()
    {
        $tenant_id = tenant_id();
        $data = [];

        if ($tenant_id) {
            $subscription = \App\Models\Subscription::where('tenant_id', $tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();

            if ($subscription) {
                $data = \App\Models\PlanFeature::where('plan_id', $subscription->plan_id)->pluck('slug')->toArray();
            }
        }

        if ($this->isModuleEnabled() && in_array('emb_signup', $data)) {
            add_filter('tenant_sidebar.main_menu', function ($menus) {
                $menus['embedded_signup'] = [
                    'type' => 'item',
                    'label' => 'embedded_signup',
                    'route' => 'tenant.embedded-signup.embsignin',
                    'icon' => 'heroicon-o-link',
                    'permission' => 'tenant.embedded-signup.embsignin',
                    'order' => 2,
                    'active_routes' => ['tenant.embedded-signup.embsignin'],
                    'feature_required' => 'emb_signup',
                    'badge' => null,
                    'external' => false,
                    'visible_when' => function () {
                        return get_tenant_setting_from_db('whatsapp', 'is_whatsmark_connected') == 0 ||
                            get_tenant_setting_from_db('whatsapp', 'is_webhook_connected') == 0;
                    },
                ];

                return $menus;
            });
        }
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return \Corbital\ModuleManager\Facades\ModuleManager::isActive('EmbeddedSignup');
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
            $this->loadTranslationsFrom(base_path('Modules/'.$this->moduleName.'/resources/lang'), $this->moduleName);
        }
    }

    /**
     * Register views.
     *
     * @return void
     */
    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/'.strtolower($this->moduleName));

        $sourcePath = base_path('Modules/'.$this->moduleName.'/resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        // Register views with both lowercase and original case to ensure compatibility
        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), $this->moduleName);
        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), strtolower($this->moduleName));
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
        if ($request->is("{$subdomain}/embedded-signup/*") || $request->is("{$subdomain}/embedded-signup/")) {
            $result = $updateChecker->validateRequest('59142549');
            if (! $result) {
                app('module.manager')->deactivate($module_name);

                return redirect()->to(tenant_route('tenant.dashboard'));
            }
        }
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\EmbeddedSignup\Console\Commands\SyncCoexistenceCommand::class,
            ]);
        }
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
