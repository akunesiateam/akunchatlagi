<x-app-layout>
    <x-slot:title>
        {{ t('transactions') }}
    </x-slot:title>
    <x-breadcrumb :items="[['label' => t('dashboard'), 'route' => route('admin.dashboard')], ['label' => t('transactions')]]" />

    <div x-init="setInterval(() => {
        Livewire.dispatch('refreshTable');
    
    }, 30000);">
        <div class="mt-8 lg:mt-0">
            <livewire:admin.tables.filament.transaction-filament-table />
        </div>
    </div>


</x-app-layout>
