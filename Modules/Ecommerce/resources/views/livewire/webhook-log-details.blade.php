<div class="px-4 md:px-0">
    <x-slot:title>
        {{ t('webhook_log_details') }}
    </x-slot:title>

    <!-- Breadcrumb at the top -->
    <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('ecom_webhooks'), 'route' => tenant_route('tenant.webhooks.index')],
        ['label' => t('ecom_webhook_logs'), 'route' => tenant_route('tenant.webhooks.logs')],
        ['label' => t('webhook_log_details')],
    ]" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 ">
        <!-- Log Details Card -->
        <x-card>
            <x-slot:header>
                <h2 class="text-xl font-semibold text-secondary-700 dark:text-secondary-300">{{ t('webhook_log_details') }}</h2>
            </x-slot:header>
            <x-slot:content>
                <div
                    class="flex justify-between p-2 bg-slate-100 dark:bg-gray-800 text-gray-500 dark:text-gray-300 font-normal text-sm">
                    <p>{{ t('status') }}</p>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $data->getStatusBadge()['class'] }}">
                        {{ $data->getStatusBadge()['text'] }}
                    </span>
                </div>
                <div class="flex justify-between p-2 mb-4 text-gray-500 dark:text-gray-300 font-normal text-sm">
                    <p>{{ t('date') }}</p>
                    <p>{{ $data->created_at->format('M d, Y h:i A') }}</p>
                </div>
                @if ($data->delivery_status)
                    <div class="flex justify-between p-2 mb-4 text-gray-500 dark:text-gray-300 font-normal text-sm">
                        <p>{{ t('delivery_status') }}</p>
                        <span
                            class="px-2 py-1 text-xs font-semibold rounded-full {{ $data->getDeliveryStatusBadge()['class'] }}">
                            {{ $data->getDeliveryStatusBadge()['text'] }}
                        </span>
                    </div>
                @endif
                @if ($data->recipient_phone)
                    <div class="flex justify-between p-2 mb-4 text-gray-500 dark:text-gray-300 font-normal text-sm">
                        <p>{{ t('recipient_phone') }}</p>
                        <p>{{ $data->recipient_phone }}</p>
                    </div>
                @endif
                @if ($data->whatsapp_message_id)
                    <div class="flex justify-between p-2 mb-4 text-gray-500 dark:text-gray-300 font-normal text-sm">
                        <p>{{ t('whatsapp_message_id') }}</p>
                        <p class="break-all font-mono text-xs">{{ $data->whatsapp_message_id }}</p>
                    </div>
                @endif
                @if ($data->failure_reason)
                    <div class="p-2 mb-4">
                        <h5 class="mb-2 text-gray-800 dark:text-gray-200">{{ t('failure_reason') }}</h5>
                        <div
                            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 text-sm rounded-md p-4">
                            {{ $data->failure_reason }}
                        </div>
                    </div>
                @endif
            </x-slot:content>
        </x-card>

        <!-- Webhook Endpoint Info -->
        <x-card class="self-start">
            <x-slot:header>
                <h2 class="text-xl font-semibold text-secondary-700 dark:text-secondary-300">{{ t('webhook_endpoint') }}</h2>
            </x-slot:header>
            <x-slot:content>
                <div
                    class="grid grid-cols-2 gap-2 border-y p-2 bg-slate-100 dark:bg-gray-800 text-gray-500 dark:text-gray-300 dark:border-gray-700 break-words break-all font-normal text-sm">
                    <p>{{ t('name') }}</p>
                    <p>{{ $data->webhookEndpoint->name ?? 'N/A' }}</p>
                </div>
                <div
                    class="grid grid-cols-2 gap-2 border-y p-2 text-gray-500 dark:text-gray-300 dark:border-gray-700 break-words break-all font-normal text-sm">
                    <p>{{ t('webhook_endpoint_id') }}</p>
                    <p>{{ $data->webhook_endpoint_id }}</p>
                </div>
                <div
                    class="grid grid-cols-2 gap-2 border-y p-2 text-gray-500 dark:text-gray-300 dark:border-gray-700 break-words break-all font-normal text-sm">
                    <p>{{ t('webhook_endpoint') }}</p>
                    <p>{{ $data->webhookEndpoint->webhook_url ?? 'N/A' }}</p>
                </div>
                <div
                    class="grid grid-cols-2 gap-2 border-y p-2 bg-slate-100 dark:bg-gray-800 text-gray-500 dark:text-gray-300 dark:border-gray-700 break-words break-all font-normal text-sm">
                    <p>{{ t('method') }}</p>
                    <p>{{ $data->webhookEndpoint->method ?? 'N/A' }}</p>
                </div>
            </x-slot:content>
        </x-card>

        <!-- Payload Data -->
        <x-card class="self-start">
            <x-slot:header>
                <div class="flex flex-col sm:flex-row items-start sm:justify-between sm:items-center mb-3">
                    <h2 class="text-xl font-semibold text-secondary-700 dark:text-secondary-300">{{ t('webhook_payload') }}</h2>

                    <span
                        class="bg-info-100 dark:bg-info-900 text-info-600 dark:text-info-300 text-xs font-medium px-2 py-1 mt-2 sm:mt-0 rounded">
                        {{ t('json_format') }}
                    </span>
                </div>
            </x-slot:header>
            <x-slot:content>
                <div class="bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-md">
                    <div
                        class="bg-slate-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm rounded-md p-4 overflow-x-auto">
                        <pre class="font-mono text-sm leading-relaxed">{{ $formattedPayload }}</pre>
                    </div>
                </div>
            </x-slot:content>
        </x-card>

        <!-- Extracted Fields -->
        @if ($data->extracted_fields)
            <x-card class="self-start">
                <x-slot:header>
                    <div class="flex flex-col sm:flex-row items-start sm:justify-between sm:items-center mb-3">
                        <x-settings-heading>
                            {{ t('extracted_fields') }}
                        </x-settings-heading>
                        <span
                            class="bg-success-100 dark:bg-success-900 text-success-600 dark:text-success-300 text-sm font-medium px-2 py-1 mt-2 sm:mt-0 rounded">
                            {{ t('processed') }}
                        </span>
                    </div>
                </x-slot:header>
                <x-slot:content>
                    <div
                        class="bg-slate-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm border border-gray-300 dark:border-gray-700 rounded-md p-4 overflow-x-auto">
                        <pre class="font-mono text-sm leading-relaxed">{{ $formattedExtractedFields }}</pre>
                    </div>
                </x-slot:content>
            </x-card>
        @endif

        <!-- Meta Response -->
        @if ($data->meta_response)
            <x-card class="self-start">
                <x-slot:header>
                    <div class="flex flex-col sm:flex-row items-start sm:justify-between sm:items-center mb-3">
                        <h2 class="text-xl font-semibold text-secondary-700 dark:text-secondary-300">{{ t('processing_response') }}</h2>

                        <span
                            class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs font-medium px-2 py-1 mt-2 sm:mt-0 rounded">
                            {{ t('system_response') }}
                        </span>
                    </div>
                </x-slot:header>
                <x-slot:content>
                    <div
                        class="bg-slate-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm border border-gray-300 dark:border-gray-700 rounded-md p-4 overflow-x-auto">
                        <pre class="font-mono text-sm leading-relaxed">{{ $formattedMetaResponse }}</pre>
                    </div>
                </x-slot:content>
            </x-card>
        @endif
    </div>
</div>
