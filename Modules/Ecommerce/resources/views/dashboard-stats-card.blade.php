<x-dashboard.stats-card title="{{ t('ecommerce_webhooks') }}" :value="$totalUsed" :limit="$totalAssistant"
    subtitle="{{ t('webhooks') }}" action="Manage" color="rose" :bg="true"
    href="{{ tenant_route('tenant.webhooks.index') }}">

    <x-slot:icon>
         <x-carbon-webhook  class="h-6 w-6 text-rose-600 dark:text-rose-400" />
       
    </x-slot:icon>
</x-dashboard.stats-card>
