<x-app-layout>
    <x-slot name="title">Webhook Log Details</x-slot>

    @livewire('Modules\Ecommerce\Livewire\WebhookLogDetails', ['logId' => $logId])

</x-app-layout>
