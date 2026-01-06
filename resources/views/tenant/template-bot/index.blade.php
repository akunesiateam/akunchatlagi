<x-app-layout>
    <x-slot:title>
        {{ t('create_template') }}
    </x-slot:title>
        <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('template_bot'), 'route' => tenant_route('tenant.templatebot.list')],
        ['label' => empty($templatebotId) ? t('create_template_bot') : t('edit_template_bot')]
    ]" />
    @if (empty($templatebotId) && $hasReachedLimit)
    <!-- Feature Limit Warning -->
    <div class="pb-3">
        <div class="rounded-md bg-warning-50 dark:bg-warning-900/30 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-warning-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                        {{ t('template_bot_limit_reached') }}
                    </h3>
                    <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                        <p>
                            {{ t('template_bot_limit_reached_message') }}
                            <a href="{{ tenant_route('tenant.subscription') }}" class="font-medium underline">
                                {{ t('upgrade_plan') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

    <div id="template-bot" class="w-full">
        <template-bot  :templatebot-id="{{ $templatebotId ?? 'null' }}"
             tenant-subdomain="{{ $subdomain }}"></template-bot>
    </div>
</x-app-layout>
<script>
    window.subdomain = @json($subdomain);
</script>

