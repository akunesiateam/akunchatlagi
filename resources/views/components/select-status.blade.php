@props(['options', 'selected', 'userId'])

@php
    $selectedKey = (string) $selected;

    $statusColorMapping = App\Enum\TenantStatus::colorMap();
    $defaultColors = App\Enum\TenantStatus::defaultColors();

    $status = $statusColorMapping[$selectedKey] ?? $defaultColors;

    $bgColor = $status['bg'] ?? $defaultColors['bg'];
    $textColor = $status['text'] ?? $defaultColors['text'];
    $dotColor = $status['dot'] ?? $defaultColors['dot'];
    $borderColor = $status['border'] ?? $defaultColors['border'];
@endphp

<div class="p-5 bg-white dark:bg-gray-800 space-y-5 transition-all duration-200" x-data="{ selectedStatus: '{{ $selectedKey }}' }">


    <!-- Status Options -->
    <div class="max-h-72 overflow-y-auto grid grid-cols-1 gap-3 p-1">
        @foreach ($options as $id => $name)
            @php
                $optionStatus = $statusColorMapping[$id] ?? $defaultColors;
                $isSelected = $selectedKey == $id;
                $selectedBg = $optionStatus['bg'] ?? 'bg-primary-50 dark:bg-primary-900/20';
                $selectedText = $optionStatus['text'] ?? 'text-primary-700 dark:text-primary-300';
                $selectedBorder = $optionStatus['border'] ?? 'border-primary-300 dark:border-primary-700';
                $defaultBorder = 'border-gray-200 dark:border-gray-700';
            @endphp

            <button type="button" wire:click="statusChanged('{{ $id }}', {{ $userId }})"
                @click="selectedStatus = '{{ $id }}'"
                class="relative flex items-center justify-between w-full px-4 py-3 rounded-lg border text-sm font-medium transition-all duration-200 focus:outline-none"
                :class="selectedStatus === '{{ $id }}'
                    ?
                    '{{ $selectedBg }} {{ $selectedBorder }} {{ $selectedText }} ring-1 ring-primary-400 dark:ring-primary-500 shadow-sm' :
                    '{{ $defaultBorder }} hover:bg-gray-50 dark:hover:bg-gray-700/40 text-gray-700 dark:text-gray-300'">

                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full {{ $optionStatus['dot'] }}"></span>
                    <span>{{ $name }}</span>
                </div>

                <!-- Checkmark -->
                <svg x-show="selectedStatus === '{{ $id }}'" x-transition
                    class="w-4 h-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        @endforeach
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }

    .max-h-72::-webkit-scrollbar {
        width: 4px;
    }

    .max-h-72::-webkit-scrollbar-thumb {
        background-color: rgba(99, 102, 241, 0.3);
        border-radius: 4px;
    }
</style>
