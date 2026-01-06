<div>
    <x-card>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    WhatsApp Business App Integration
                </h3>
                <div class="flex items-center space-x-2">
                    @if($syncStatus && $syncStatus->isFullySynced())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Fully Synced
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Syncing
                        </span>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button wire:click="switchTab('status')"
                            class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'status' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        Sync Status
                    </button>
                    <button wire:click="switchTab('contacts')"
                            class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contacts' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        Contacts ({{ $stats['contacts_synced'] ?? 0 }})
                    </button>
                    <button wire:click="switchTab('messages')"
                            class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'messages' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        Messages ({{ $stats['messages_synced'] ?? 0 }})
                    </button>
                    <button wire:click="switchTab('threads')"
                            class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'threads' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        Conversations
                    </button>
                </nav>
            </div>

            {{-- Sync Status Tab --}}
            @if($activeTab === 'status')
                <div class="space-y-6">
                    {{-- Overall Progress --}}
                    @if($syncStatus)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Synchronization Progress</h4>

                            <div class="space-y-4">
                                {{-- Overall Progress Bar --}}
                                <div>
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <span>Overall Progress</span>
                                        <span>{{ $syncStatus->getSyncPercentage() }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $syncStatus->getSyncPercentage() }}%"></div>
                                    </div>
                                </div>

                                {{-- Contacts Sync Status --}}
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($syncStatus->contacts_sync_status === 'completed')
                                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @elseif($syncStatus->contacts_sync_status === 'in_progress')
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                Contacts Synchronization
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $syncStatus->total_contacts_synced }} contacts synced
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs rounded-full {{
                                            $syncStatus->contacts_sync_status === 'completed' ? 'bg-green-100 text-green-800' :
                                            ($syncStatus->contacts_sync_status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $syncStatus->contacts_sync_status)) }}
                                        </span>
                                        @if($syncStatus->contacts_sync_status === 'failed')
                                            <button wire:click="retrySyncContacts" class="text-blue-600 hover:text-blue-800 text-xs">
                                                Retry
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- History Sync Status --}}
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($syncStatus->history_sync_status === 'completed')
                                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @elseif($syncStatus->history_sync_status === 'in_progress')
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            @elseif($syncStatus->history_sync_status === 'declined')
                                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                Message History Synchronization
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $syncStatus->total_messages_synced }} messages synced ({{ $syncStatus->history_sync_progress }}%)
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs rounded-full {{
                                            $syncStatus->history_sync_status === 'completed' ? 'bg-green-100 text-green-800' :
                                            ($syncStatus->history_sync_status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                            ($syncStatus->history_sync_status === 'declined' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $syncStatus->history_sync_status)) }}
                                        </span>
                                        @if($syncStatus->history_sync_status === 'failed')
                                            <button wire:click="retrySyncHistory" class="text-blue-600 hover:text-blue-800 text-xs">
                                                Retry
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($syncStatus->last_sync_error)
                                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                                    <p class="text-sm text-red-800 dark:text-red-200">
                                        <strong>Last Error:</strong> {{ $syncStatus->last_sync_error }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No sync status available</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Contacts Tab --}}
            @if($activeTab === 'contacts')
                <div class="space-y-4">
                    {{-- Search --}}
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="text"
                                   wire:model.live.debounce.300ms="searchContacts"
                                   placeholder="Search contacts..."
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                    </div>

                    {{-- Contacts List --}}
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($contacts as $contact)
                                <li class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $contact->full_name ?: ($contact->first_name . ' ' . $contact->last_name) }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $contact->contact_phone_number }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs rounded-full {{
                                                $contact->action === 'added' ? 'bg-green-100 text-green-800' :
                                                ($contact->action === 'updated' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')
                                            }}">
                                                {{ ucfirst($contact->action) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $contact->synced_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No contacts found
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    {{ $contacts->links() }}
                </div>
            @endif

            {{-- Messages Tab --}}
            @if($activeTab === 'messages')
                <div class="space-y-4">
                    @if($selectedThread)
                        {{-- Back to all messages --}}
                        <div class="flex items-center space-x-2">
                            <button wire:click="$set('selectedThread', '')" class="text-blue-600 hover:text-blue-800 text-sm">
                                ← Back to all messages
                            </button>
                            <span class="text-sm text-gray-500">Conversation with {{ $selectedThread }}</span>
                        </div>
                    @else
                        {{-- Search --}}
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <input type="text"
                                       wire:model.live.debounce.300ms="searchMessages"
                                       placeholder="Search messages..."
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </div>
                        </div>
                    @endif

                    {{-- Messages List --}}
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($messages as $message)
                                <li class="px-6 py-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 {{ $message->is_echo ? 'bg-green-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                                                @if($message->is_echo)
                                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $message->from_phone_number }}
                                                    @if($message->to_phone_number)
                                                        → {{ $message->to_phone_number }}
                                                    @endif
                                                </p>
                                                <div class="flex items-center space-x-2">
                                                    @if($message->is_echo)
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Echo</span>
                                                    @endif
                                                    @if($message->is_history)
                                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">History</span>
                                                    @endif
                                                    <span class="text-xs text-gray-500">{{ $message->getFormattedTimestamp() }}</span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                {{ $message->getContentText() }}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No messages found
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    @if(!$selectedThread)
                        {{ $messages->links() }}
                    @endif
                </div>
            @endif

            {{-- Threads Tab --}}
            @if($activeTab === 'threads')
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($messageThreads as $thread)
                                <li class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                    wire:click="selectThread('{{ $thread->thread_id }}')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $thread->thread_id }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $thread->message_count }} messages
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($thread->last_message_at)->diffForHumans() }}
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No conversations found
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif
        </x-slot>
    </x-card>
</div>
