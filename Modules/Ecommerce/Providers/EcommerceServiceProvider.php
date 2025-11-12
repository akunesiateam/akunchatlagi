<?php

namespace Modules\Ecommerce\Providers;

use App\Models\PlanFeature;
use App\Models\Subscription;
use Corbital\ModuleManager\Classes\ModuleUpdateChecker;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Ecommerce\Http\Middleware\EcommerceMiddleware;
use Modules\Ecommerce\Models\WebhookEndpoints;
use Modules\Ecommerce\Models\WebhookLogs;

class EcommerceServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName = 'Ecommerce';

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
        $this->registerLicenseHooks($this->moduleName);
        $this->registerCommands();
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
    }

    /**
     * Register Livewire components.
     *
     * @return void
     */
    protected function registerLivewireComponents()
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('Modules\Ecommerce\Livewire\WebhookLogsTable', \Modules\Ecommerce\Livewire\WebhookLogsTable::class);
            Livewire::component('Modules\Ecommerce\Livewire\WebhookLogsList', \Modules\Ecommerce\Livewire\WebhookLogsList::class);
            Livewire::component('Modules\Ecommerce\Livewire\WebhookLogDetails', \Modules\Ecommerce\Livewire\WebhookLogDetails::class);
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
        $router->aliasMiddleware('ecommerce.token', EcommerceMiddleware::class);
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
            $subscription = Subscription::where('tenant_id', $tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();
            if ($subscription) {
                $data = PlanFeature::where('plan_id', $subscription->plan_id)->pluck('slug')->toArray();
            }
        }
        if ($this->isModuleEnabled() && in_array('ecommerce_webhooks', $data)) {
            add_filter('tenant_sidebar.main_menu', function ($menus) {
                $menus['ecommerce_section'] = [
                    'type' => 'section',
                    'label' => 'ecommerce',
                    'icon' => null,
                    'permission' => 'tenant.ecommerce_webhook.view',
                    'order' => 6,
                    'section_id' => 'ecommerce',
                    'children' => [
                        'ecommerce_webhooks' => [
                            'type' => 'item',
                            'label' => t('ecom_webhooks'),
                            'route' => 'tenant.webhooks.index',
                            'icon' => 'heroicon-o-shopping-cart',
                            'permission' => 'tenant.ecommerce_webhook.view',
                            'order' => 1,
                            'active_routes' => ['tenant.webhooks.index'],
                            'feature_required' => 'ecommerce_webhooks',
                        ],
                        'ecommerce_webhook_logs' => [
                            'type' => 'item',
                            'label' => t('ecom_webhook_logs'),
                            'route' => 'tenant.webhooks.logs',
                            'icon' => 'heroicon-o-document-text',
                            'permission' => 'tenant.ecommerce_webhook.view',
                            'order' => 2,
                            'active_routes' => ['tenant.webhooks.logs'],
                            'feature_required' => 'ecommerce_webhooks',
                        ],
                    ],
                ];

                return $menus;
            });

            add_action('after_dashboard_stats_card', function () {
                $activeSubscription = Subscription::where('tenant_id', tenant_id())
                    ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
                    ->with(['plan', 'plan.features'])
                    ->latest()
                    ->first();

                $planFeatures = $activeSubscription->plan->features()->get();
                $assistantFeature = collect($planFeatures)->firstWhere('slug', 'ecommerce_webhooks');

                $totalAssistant = $assistantFeature['value'] == -1 ? t('unlimited') : $assistantFeature['value'] ?? 0;
                $totalUsed = WebhookEndpoints::where('tenant_id', tenant_id())->count();
                echo view('Ecommerce::dashboard-stats-card', compact('totalUsed', 'totalAssistant'))->render();
            });
        }

        add_action('whatsapp_webhook_status_updated', function ($data) {
            $id = $data['status']['id'];
            $status_value = $data['status']['status'];

            $status_message = null;
            $errors = $data['status']['errors'] ?? [];

            $error_data = array_column($errors, 'error_data');
            $details = array_column($error_data, 'details');

            $status_message = reset($details) ?: null;

            WebhookLogs::where('tenant_id', $data['tenant_id'])->where('whatsapp_message_id', $id)->update([
                'delivery_status' => $status_value,
                'failure_reason' => $status_message,
            ]);
        });

        add_action('after_scheduling_tasks_registered', function (Schedule $schedule) {
            $schedule->command('queue:work --queue=ecommerce_webhook --stop-when-empty --sleep=3 --tries=3 --timeout=60 --backoff=5 --max-time=3600 --max-jobs=100')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return \Corbital\ModuleManager\Facades\ModuleManager::isActive('Ecommerce');
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
                if ($request->is("{$subdomain}/webhooks/*") || $request->is("{$subdomain}/webhooks/")) {
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
        if ($request->is("{$subdomain}/webhooks/*") || $request->is("{$subdomain}/webhooks/")) {
            $result = $updateChecker->validateRequest('59498277');
            if (! $result) {
                app('module.manager')->deactivate($module_name);

                return redirect()->to(tenant_route('tenant.dashboard'));
            }
        }
    }

    /**
     * Register Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([]);
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
