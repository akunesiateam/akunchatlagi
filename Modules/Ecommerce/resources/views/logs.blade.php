<x-app-layout>
    <x-slot name="title">{{ t('ecom_webhook_logs') }}</x-slot>

    <div class="space-y-6">
        <!-- Breadcrumb at the top -->
      <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('ecom_webhook_logs')],
    ]" />

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-page-heading>{{ t('ecom_webhook_logs') }}</x-page-heading>
               
            </div>
        </div>

        <!-- Include the Livewire Component -->
        @livewire('Modules\Ecommerce\Livewire\WebhookLogsList')
    </div>
</x-app-layout>
