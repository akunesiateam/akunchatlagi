<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters and Search --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
            {{-- Search --}}
            <div class="flex-1 max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ t('search') }}..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
            </div>

            {{-- Filters --}}
            <div class="flex gap-2">
                <button wire:click="toggleMissingFilter"
                    class="px-3 py-2 text-sm rounded-lg border {{ $showMissingOnly ? 'bg-red-100 text-red-800 border-red-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ t('missing') }} ({{ $records->where('value', '')->count() }})
                </button>

                <button wire:click="toggleTranslatedFilter"
                    class="px-3 py-2 text-sm rounded-lg border {{ $showTranslatedOnly ? 'bg-green-100 text-green-800 border-green-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ t('completed') }} ({{ $records->where('value', '!=', '')->count() }})
                </button>

                @if ($search || $showMissingOnly || $showTranslatedOnly)
                    <button wire:click="clearFilters"
                        class="px-3 py-2 text-sm rounded-lg bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200">
                        {{ t('clear') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-900">
                            <button wire:click="sortBy('key')" class="flex items-center gap-1 hover:text-primary-600">
                                {{ t('key') }}
                                @if ($sortColumn === 'key')
                                    @if ($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-900">
                            <button wire:click="sortBy('english_value')"
                                class="flex items-center gap-1 hover:text-primary-600">
                                {{ t('english') }}
                                @if ($sortColumn === 'english_value')
                                    @if ($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-900">
                            <button wire:click="sortBy('value')" class="flex items-center gap-1 hover:text-primary-600">
                                {{ $languageName }}
                                @if ($sortColumn === 'value')
                                    @if ($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-900">
                            {{ t('status') }}
                        </th>
                        <th class="text-right px-4 py-3 text-sm font-medium text-gray-900">
                            {{ t('actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900 font-mono">
                                {{ $record['key'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs">
                                <div class="truncate" title="{{ $record['english_value'] }}">
                                    {{ Str::limit($record['english_value'], 100) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs">
                                @if ($editingKey === $record['key'])
                                    <div class="space-y-2">
                                        <textarea wire:model="editingValue"
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            rows="3"></textarea>
                                        <div class="flex gap-1">
                                            <button wire:click="saveTranslation"
                                                class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                {{ t('save') }}
                                            </button>
                                            <button wire:click="cancelEdit"
                                                class="px-2 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600">
                                                {{ t('cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    @if (empty($record['value']))
                                        <span class="text-gray-400 italic">{{ t('not_translated') }}</span>
                                    @else
                                        <div class="truncate" title="{{ $record['value'] }}">
                                            {{ Str::limit($record['value'], 100) }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if (empty($record['value']))
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                        {{ t('missing') }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                        {{ t('completed') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                @if ($editingKey !== $record['key'])
                                    <button
                                        wire:click="editTranslation('{{ $record['key'] }}', '{{ addslashes($record['value']) }}')"
                                        class="px-2 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700">
                                        {{ t('edit') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                {{ t('no_results_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($records->hasPages())
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
