<?php

namespace Modules\ThemeBuilder\Providers;

use Corbital\ModuleManager\Classes\ModuleUpdateChecker;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\ThemeBuilder\Http\Middleware\ThemeBuilderMiddleware;

class ThemeBuilderServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName = 'ThemeBuilder';

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
        $this->registerLicenseHooks($this->moduleName);
        $this->registerHooks();
        $this->registerMiddleware();

        // Add assets() relationship to Theme model via macro
        \App\Models\Theme::macro('assets', function () {
            return $this->hasMany(\Modules\ThemeBuilder\Models\ThemeAsset::class);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register module bindings
        $this->registerAdminMenuItem();
    }

    /**
     * Register admin menu item for theme settings.
     */
    private function registerAdminMenuItem(): void
    {
        add_filter('admin_sidebar.setup_menus', function ($menu) {
            $menu['theme']['route'] = 'admin.theme.list';
            $menu['theme']['active_routes'] = ['admin.theme.list', 'admin.theme.*'];

            return $menu;
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
     * Register middleware for the FBLead module.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('theme.builder', ThemeBuilderMiddleware::class);
    }

    /**
     * Register license hooks for the EmbeddedSignup module.
     *
     * @return void
     */
    protected function registerLicenseHooks($module_name)
    {
        add_action('app.middleware.redirect_if_not_installed', function ($request) use ($module_name) {
            if ($request->is('admin/theme-list') || $request->is('admin/theme/customize/*')) {
                if (app('module.hooks')->requiresEnvatoValidation($module_name)) {
                    app('module.manager')->deactivate($module_name);

                    return redirect()->route('admin.dashboard');
                }
            }
        });

        add_action('app.middleware.validate_module', function ($request) use ($module_name) {
            $this->validateModuleLicense($request, $module_name);
        });
    }

    protected function validateModuleLicense($request, $module_name)
    {
        $updateChecker = new ModuleUpdateChecker;
        if ($request->is('admin/theme-list') || $request->is('admin/theme/customize/*')) {
            $result = $updateChecker->validateRequest('60395541');
            if (! $result) {
                app('module.manager')->deactivate($module_name);

                return redirect()->route('admin.dashboard');
            }
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
            module_path($this->moduleName, 'Config/config.php'), $this->moduleName
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

        $this->loadViewsFrom(array_merge([$sourcePath], [
            $viewPath,
        ]), $this->moduleName);
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

    public function registerHooks()
    {
        // Example hook registration
        add_filter('render_landing_page', function ($data) {
            $theme_data = $data['theme'];
            if (! empty($theme_data)) {
                if ($theme_data['type'] != 'core' && ! empty($theme_data['payload'])) {
                    $data['view'] = 'ThemeBuilder::landing_page';
                }
            }

            return $data;
        }, 10, 1);

        add_filter('active_admin_setup_menus', function ($menus) {
            $menus[] = 'admin.theme.*';

            return $menus;
        }, 10, 1);
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
