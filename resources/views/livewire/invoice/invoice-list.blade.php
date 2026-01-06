<div>
    <x-slot:title>
        {{ t('invoices') }}
    </x-slot:title>
    <x-breadcrumb :items="[['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')], ['label' => t('invoices')]]" />

    <div class="mt-8 lg:mt-0" wire:poll.30s="refreshTable">
        <livewire:tenant.tables.filament.invoice-filament-table />
    </div>
</div>
