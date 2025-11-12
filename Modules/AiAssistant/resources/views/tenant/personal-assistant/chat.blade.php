<x-app-layout>
    <x-slot:title>
        {{ t('chat_with') }} {{ $assistant->name }}
    </x-slot:title>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-gray-100">
                {{ $assistant->name }}
            </h2>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-button.secondary href="{{ tenant_route('tenant.personal-assistants.index') }}"
                class="inline-flex items-center">
                <x-heroicon-o-arrow-small-left class="w-4 h-4 mr-2" />
                {{ t('back_to_assistant') }}
            </x-button.secondary>

            <x-button.soft-danger x-on:click="$dispatch('open-modal', 'chat-clear-history')">
                <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                {{ t('clear_chat') }}
            </x-button.soft-danger>

            <span
                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $assistant->hasOpenAIAssistant() ? 'bg-success-100 text-success-800 border border-success-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                <span
                    class="w-2 h-2 rounded-full mr-2 {{ $assistant->hasOpenAIAssistant() ? 'bg-success-500' : 'bg-amber-500' }}"></span>
                {{ $assistant->hasOpenAIAssistant() ? 'Synced' : 'Not Synced' }}
            </span>
        </div>
    </div>

    <div x-data="chatInterface()" class="mx-auto py-3">

        <x-dynamic-alert type="warning" class="mb-3">
            <p class="text-base">
                <strong>Note:</strong> This chat interface is only being used for testing and preview purposes.
            </p>
        </x-dynamic-alert>

        <!-- Main Chat Container -->
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            <!-- Chat Section -->
            <div class="xl:col-span-3">

                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                    <!-- Chat Header -->
                    <div class="bg-gradient-to-r from-primary-600 to-purple-600 text-white p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                    <x-heroicon-o-sparkles class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $assistant->name }}</h3>
                                    <p class="text-sm text-primary-100 opacity-90">
                                        {{ $assistant->model ?? 'gpt-4o-mini' }}</p>
                                </div>
                            </div>
                            <div class="text-right hidden sm:block">
                                <p class="text-sm text-primary-100">{{ $assistant->documents->count() }}
                                    {{ t('documents') }}</p>
                                <p class="text-xs text-primary-200 opacity-75">{{ t('temp') }}
                                    {{ $assistant->temperature ?? 0.7 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div x-ref="messagesContainer"
                        class="h-[32rem] sm:h-[34rem] overflow-y-auto p-4 sm:p-6 space-y-4 bg-gray-50 dark:bg-gray-900">

                        <!-- Hidden div to initialize server messages -->
                        @if ($messages->count() > 0)
                            <div style="display: none;" x-init="initServerMessages(@js(
    $messages
        ->map(function ($message) {
            return [
                'content' => $message->message,
                'sender' => $message->sender_id,
                'time' => $message->created_at->format('H:i'),
            ];
        })
        ->toArray(),
))">
                            </div>
                        @endif

                        <!-- Alpine.js rendered messages -->
                        <template x-if="messages.length === 0 && !isTyping">
                            <div class="text-center py-12">
                                <div
                                    class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ t('start_conversation') }}
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto"> {{ t('ask_assistant') }}
                                </p>
                            </div>
                        </template>

                        <!-- Message Loop -->
                        <template x-for="(message, index) in messages" :key="index">
                            <div class="flex"
                                :class="message.sender === 'assistant' ? 'justify-start' : 'justify-end'">
                                <div class="max-w-[85%] sm:max-w-md lg:max-w-lg">
                                    <div class="flex items-start gap-3"
                                        :class="message.sender === 'assistant' ? '' : 'flex-row-reverse'">
                                        <!-- Avatar -->
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="getAvatarClasses(message.sender, message.isError)">
                                            <!-- Assistant Icons -->
                                            <template x-if="message.sender === 'assistant' && message.isError">
                                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 sm:w-5 sm:h-5" />

                                            </template>
                                            <template x-if="message.sender === 'assistant' && !message.isError">
                                                <x-heroicon-o-sparkles class="w-4 h-4 sm:w-5 sm:h-5" />
                                            </template>
                                            <!-- User Icon -->
                                            <template
                                                x-if="message.sender === 'user' || (message.sender !== 'assistant' && !message.isError)">
                                                <x-heroicon-o-user class="w-4 h-4 sm:w-5 sm:h-5 " />
                                            </template>
                                        </div>

                                        <!-- Message Content -->
                                        <div class="flex flex-col space-y-1">
                                            <div class="px-4 py-3 rounded-lg"
                                                :class="getMessageClasses(message.sender, message.isError)">
                                                <div class="text-sm leading-relaxed whitespace-pre-wrap"
                                                    x-html="formatMessage(message.content)"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 px-1"
                                                :class="message.sender === 'assistant' ? 'text-left' : 'text-right'"
                                                x-text="message.time">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Typing Indicator -->
                        <div x-show="isTyping" x-transition class="flex justify-start">
                            <div class="max-w-[85%] sm:max-w-md lg:max-w-lg">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-sparkles class="w-4 h-4 sm:w-5 sm:h-5" />
                                    </div>
                                    <div
                                        class="px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl">
                                        <div class="flex space-x-1">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                                style="animation-delay: 0.1s"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                                style="animation-delay: 0.2s"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-white dark:bg-gray-800">
                        <form @submit.prevent="sendMessage()"
                            class="mx-auto w-full flex items-center justify-center gap-2 sm:gap-3">
                            @csrf

                            <!-- Textarea -->
                            <textarea x-model="messageText" x-ref="messageInput" @input="autoResize()"
                                @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()" :disabled="isLoading" rows="1"
                                placeholder="Type your message..." required
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-0 focus:ring-primary-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 resize-none text-sm sm:text-base disabled:opacity-50"></textarea>

                            <!-- Send Button -->
                            <button type="submit" :disabled="isLoading || !messageText.trim()"
                                x-on:click="sendMessage()"
                                class="flex items-center gap-1 px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-primary-100 dark:bg-gray-700 text-primary-700 dark:text-gray-200 hover:bg-primary-200 dark:hover:bg-gray-600 text-sm sm:text-base font-base transition">
                                <!-- Icon -->
                                <x-heroicon-o-paper-airplane x-show="!isLoading" class="w-5 h-5" />

                                <!-- Spinner -->
                                <svg x-show="isLoading" class="w-5 h-5 animate-spin" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>

                                <span x-show="!isLoading">{{ t('send') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Knowledge Base Sidebar -->
            <div class="xl:col-span-1">
                @if ($assistant->documents->count() > 0)
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-primary-600" />
                            {{ t('knowledge_base') }}
                        </h3>
                        <div class="space-y-3">
                            @foreach ($assistant->documents as $document)
                                <div
                                    class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div
                                        class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-document-text class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                                            title="{{ $document->original_filename }}">
                                            {{ $document->original_filename }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $document->isUploadedToOpenAI() ? 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400' }}">
                                                {{ $document->file_type }}
                                            </span>
                                            <span
                                                class="text-xs {{ $document->isUploadedToOpenAI() ? 'text-success-600 dark:text-success-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                {{ $document->isUploadedToOpenAI() ? 'Synced' : 'Pending' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="text-center">
                            <div
                                class="w-12 h-12 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <x-heroicon-o-document-plus class="w-6 h-6 text-gray-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ t('no_documents') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ t('upload_documents') }}</p>
                            <a href="{{ tenant_route('tenant.personal-assistants.edit', ['assistant' => $assistant]) }}"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition-colors">
                                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                                {{ t('upload_doc') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Clear Chat Modal -->
        <x-modal name="chat-clear-history" maxWidth="lg">
            <div class="w-full px-4 py-4 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-danger-100 dark:bg-danger-900/20 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('clear_chat_history') }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ t('chat_delete_confirmation') }}
                </p>
                <div class="flex justify-end gap-3 pt-2">
                    <button x-on:click="$dispatch('close-modal', 'chat-clear-history')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        {{ t('cancel') }}
                    </button>
                    <button @click.prevent="clearChat(); $dispatch('close-modal', 'chat-clear-history');"
                        :disabled="isClearing"
                        class="px-4 py-2 text-sm font-medium text-white bg-danger-600 hover:bg-danger-700 disabled:bg-danger-400 rounded-lg transition-colors disabled:cursor-not-allowed">
                        <span>{{ t('clear_chat') }}</span>
                    </button>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function chatInterface() {
            return {
                // Reactive Data State
                messageText: '',
                messages: [],
                isLoading: false,
                isTyping: false,
                isClearing: false,

                // Initialize component
                init() {
                    this.$nextTick(() => {
                        this.$refs.messageInput.focus();
                        this.scrollToBottom();
                    });
                },

                // Initialize server-side messages (called during x-init)
                initServerMessages(serverMessages) {
                    this.messages = serverMessages.map(msg => ({
                        content: msg.content,
                        sender: msg.sender,
                        time: msg.time,
                        isError: false
                    }));
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                },

                // Textarea auto-resize
                autoResize() {
                    const textarea = this.$refs.messageInput;
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                },

                // Scroll to bottom utility
                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        container.scrollTop = container.scrollHeight;
                    });
                },

                // Format message content with markdown support
                formatMessage(content) {
                    let formatted = content;

                    // Convert line breaks
                    formatted = formatted.replace(/\n/g, '<br>');

                    // Convert **bold text** to <strong>
                    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

                    // Convert *italic text* to <em>
                    formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');

                    // Convert numbered lists (1. 2. 3.)
                    formatted = formatted.replace(/^(\d+)\.\s+(.*)$/gm,
                        '<div class="ml-4 mb-1"><span class="font-semibold text-primary-600">$1.</span> $2</div>');

                    // Convert bullet points starting with - or •
                    formatted = formatted.replace(/^[-•]\s+(.*)$/gm,
                        '<div class="ml-4 mb-1 flex items-start"><span class="text-primary-600 mr-2">•</span><span>$1</span></div>'
                    );

                    // Convert sub-bullet points (with extra indentation)
                    formatted = formatted.replace(/^\s{2,}[-•]\s+(.*)$/gm,
                        '<div class="ml-8 mb-1 flex items-start"><span class="text-gray-500 mr-2">◦</span><span class="text-gray-700">$1</span></div>'
                    );

                    // Convert headers (# ## ###)
                    formatted = formatted.replace(/^###\s+(.*)$/gm,
                        '<h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 mt-3">$1</h5>');
                    formatted = formatted.replace(/^##\s+(.*)$/gm,
                        '<h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 mt-3">$1</h4>');
                    formatted = formatted.replace(/^#\s+(.*)$/gm,
                        '<h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-3 mt-4">$1</h3>');

                    // Convert source citations 【source】 to styled badges
                    formatted = formatted.replace(/【([^】]+)】/g,
                        '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900/20 dark:text-info-400 ml-1">$1</span>'
                    );

                    // Convert code blocks `code`
                    formatted = formatted.replace(/`([^`]+)`/g,
                        '<code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-sm font-mono">$1</code>');

                    return formatted;
                },

                // Get avatar CSS classes based on sender and error state
                getAvatarClasses(sender, isError) {
                    if (sender === 'assistant') {
                        return isError ?
                            'bg-danger-100 text-danger-600 dark:bg-danger-900/50 dark:text-danger-400' :
                            'bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400';
                    }
                    return 'bg-primary-100 text-primary-600';
                },

                // Get message bubble CSS classes
                getMessageClasses(sender, isError) {
                    if (sender === 'assistant') {
                        return isError ?
                            'bg-danger-50 text-danger-800 border border-danger-200 dark:bg-danger-900/20 dark:text-danger-400 dark:border-danger-800' :
                            'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700';
                    }
                    return 'bg-primary-500 text-white';
                },

                // Add message to Alpine state
                addMessage(content, sender, isError = false) {
                    const time = new Date().toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    this.messages.push({
                        content: content,
                        sender: sender,
                        time: time,
                        isError: isError
                    });

                    this.scrollToBottom();
                },

                // Send message to server
                async sendMessage() {
                    const message = this.messageText.trim();
                    if (!message || this.isLoading) return;

                    this.isLoading = true;
                    const userMessage = message;
                    this.messageText = '';
                    this.$refs.messageInput.style.height = 'auto';

                    // Add user message to Alpine state
                    this.addMessage(userMessage, 'user');
                    this.isTyping = true;

                    try {
                        const response = await fetch(
                            '{{ tenant_route('tenant.personal-assistants.send-message', ['assistant' => $assistant]) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                },
                                body: JSON.stringify({
                                    message: userMessage
                                })
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.addMessage(data.message, 'assistant');
                        } else {
                            if (data.errors && data.errors.message) {
                                showNotification(data.errors.message[0], 'danger');
                            }
                            this.addMessage('Sorry, I encountered an error: ' + (data.error || 'Unknown error'),
                                'assistant', true);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.addMessage('Sorry, I encountered a connection error. Please try again.', 'assistant',
                            true);
                    } finally {
                        this.isLoading = false;
                        this.isTyping = false;
                        this.$refs.messageInput.focus();
                    }
                },

                // Clear chat history
                async clearChat() {
                    if (this.isClearing) return;

                    this.isClearing = true;

                    try {
                        const response = await fetch(
                            '{{ tenant_route('tenant.personal-assistants.clear-chat', ['assistant' => $assistant]) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Clear messages from Alpine state
                            this.messages = [];
                        } else {
                            alert('Failed to clear chat: ' + (data.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while clearing the chat.');
                    } finally {
                        this.isClearing = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
