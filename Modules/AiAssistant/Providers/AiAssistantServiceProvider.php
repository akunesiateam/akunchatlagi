<?php

namespace Modules\AiAssistant\Providers;

use App\Models\Subscription;
use App\Models\Tenant\Chat;
use Corbital\ModuleManager\Classes\ModuleUpdateChecker;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\AiAssistant\Console\Commands\ProcessDocumentManually;
use Modules\AiAssistant\Console\Commands\ProcessPendingDocuments;
use Modules\AiAssistant\Http\Middleware\AiAssistantMiddleware;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Traits\AiAssistant;

class AiAssistantServiceProvider extends ServiceProvider
{
    use AiAssistant;

    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName = 'AiAssistant';

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
        $router->aliasMiddleware('assistant.token', AiAssistantMiddleware::class);
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

        if ($this->isModuleEnabled() && in_array('ai_personal_assistants', $data)) {
            add_filter('tenant_sidebar.main_menu', function ($menus) {
                $menus['marketing_section']['permission'][] = 'tenant.personal_assistant.view';
                $menus['marketing_section']['children']['ai_assistant'] = [
                    'type' => 'item',
                    'label' => 'AI Assistant',
                    'route' => 'tenant.personal-assistants.index',
                    'icon' => 'heroicon-o-sparkles',
                    'permission' => 'tenant.personal_assistant.view',
                    'feature_required' => 'ai_personal_assistants',
                    'order' => 5,
                    'active_routes' => ['tenant.personal-assistants.index'],
                    'badge' => null,
                    'external' => false,
                ];

                return $menus;
            });

            add_action('messagebot.after_fileupload_button', function ($errors) {
                echo view('AiAssistant::tenant.personal-assistant.components.tab-button', compact('errors'))->render();
            });

            add_action('messagebot.personal_assistant_tab', function ($selectedAssistantId = null) {
                $personalAssistant = PersonalAssistant::select('id', 'name')->get();
                echo view('AiAssistant::tenant.personal-assistant.components.messagebot-tab', compact('personalAssistant', 'selectedAssistantId'))->render();
            });

            add_filter('botflow.personal_assistant', function () {
                return PersonalAssistant::select('id', 'name')->get()->toArray();
            });

            add_filter('whatsmark_tenant_settings_navigation', function ($items) {
                $items['ai_assistant'] = [
                    'label' => t('ai_assistant'),
                    'route' => 'tenant.personal-assistants.settings',
                    'icon' => 'heroicon-o-sparkles',
                ];

                return $items;
            });

            add_action('after_dashboard_stats_card', function () {
                $activeSubscription = Subscription::where('tenant_id', tenant_id())
                    ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
                    ->with(['plan', 'plan.features'])
                    ->latest()
                    ->first();

                $planFeatures = $activeSubscription->plan->features()->get();
                $assistantFeature = collect($planFeatures)->firstWhere('slug', 'ai_personal_assistants');

                $totalAssistant = $assistantFeature['value'] == -1 ? t('unlimited') : $assistantFeature['value'] ?? 0;
                $totalUsed = PersonalAssistant::where('tenant_id', tenant_id())->count();
                echo view('AiAssistant::tenant.personal-assistant.components.dashboard-stats-card', compact('totalUsed', 'totalAssistant'))->render();
            });

            add_filter('message_bot.before_save', function ($data) {
                $data['message_bot']->assistant_id = empty($data['livewire']->selectedAssistantId) ? 0 : intval($data['livewire']->selectedAssistantId) ?? 0;

                return $data['message_bot'];
            }, 10, 2);
        }

        if ($this->isModuleEnabled()) {
            add_action('after_scheduling_tasks_registered', function (Schedule $schedule) {
                $schedule->command('documents:process-pending')
                    ->everyMinute()
                    ->withoutOverlapping();
            });

            add_action('before_process_bot_sending', function ($data) {
                $chatInteraction = Chat::fromTenant($data['tenant_subdomain'])->where('receiver_id', $data['message']['from'])->first();
                if ($this->shouldProcessAIChat($chatInteraction, $data['trigger_msg'])) {
                    $this->processAIMessage($chatInteraction, $data['trigger_msg']);
                    exit;
                }

                if ($chatInteraction->is_ai_chat || $chatInteraction->is_bots_stoped) {
                    exit;
                }
            });

            add_action('before_process_messagebot_sending_message', function ($data) {
                if (! empty($data['message']['assistant_id'])) {
                    $chatInteraction = Chat::fromTenant($data['tenant_subdomain'])->where('receiver_id', $data['contact_number'])->first();
                    $this->initializeAIChat($chatInteraction, $data['message']['assistant_id'], $data['trigger_msg']);

                    return;
                }
            });

            add_action('before_send_flow_message', function ($data) {
                if ($data['node_type'] == 'aiAssistant') {
                    $chatInteraction = Chat::fromTenant($data['tenant_subdomain'])->where('receiver_id', $data['contact_number'])->first();
                    $this->initializeAIChat($chatInteraction, $data['node_data']['output'][0]['personal_assistant'], $data['context']['trigger_message']);

                    return true;
                }
            }, 0, 1);
        }
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return \Corbital\ModuleManager\Facades\ModuleManager::isActive('AiAssistant');
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
                if ($request->is("{$subdomain}/personal-assistants/*") || $request->is("{$subdomain}/personal-assistants/")) {
                    if (app('module.hooks')->requiresEnvatoValidation($module_name)) {
                        // app('module.manager')->deactivate($module_name);

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
        if ($request->is("{$subdomain}/personal-assistants/*") || $request->is("{$subdomain}/personal-assistants/")) {
            $result = $updateChecker->validateRequest('59227043');
            if (! $result) {
                // app('module.manager')->deactivate($module_name);

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
        $this->commands([
            ProcessDocumentManually::class,
            ProcessPendingDocuments::class,
        ]);
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
