<div>
    <x-slot:title>
        {{ t('invoices') }}
    </x-slot:title>
    <x-breadcrumb :items="[['label' => t('dashboard'), 'route' => route('admin.dashboard')], ['label' => t('invoices')]]" />

    <div class="mt-8 lg:mt-0" wire:poll.30s="refreshTable">
        <livewire:admin.tables.filament.invoices-filament-table />
    </div>

</div>
