@php
    $method = strtoupper($endpoint['httpMethods'][0]);
    $methodColors = [
        'GET' => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700',
        'POST' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700',
        'PUT' => 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700',
        'PATCH' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-700',
        'DELETE' => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700',
    ];
    $methodColor = $methodColors[$method] ?? 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-700';
@endphp

<div id="{{ $endpoint['fullSlug'] }}" class="mb-12 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Endpoint Header -->
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold border {{ $methodColor }}">
                        {{ $method }}
                    </span>
                    @if($endpoint['authenticated'])
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Requires Authentication
                        </span>
                    @endif
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $endpoint['title'] }}</h3>
                @if(!empty($endpoint['description']))
                    <div class="prose dark:prose-invert max-w-none">
                        {!! Parsedown::instance()->text($endpoint['description']) !!}
                    </div>
                @endif
            </div>
        </div>

        <!-- Endpoint URL -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <div class="flex items-center">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-3">{{ $method }}</span>
                <code class="flex-1 text-gray-900 dark:text-white font-mono bg-transparent">{{ $endpoint['uri'] }}</code>
                <button onclick="copyToClipboard('{{ $endpoint['uri'] }}')" class="ml-3 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Parameters -->
        @if(!empty($endpoint['queryParameters']) || !empty($endpoint['bodyParameters']) || !empty($endpoint['urlParameters']))
            <div class="mb-8">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Parameters
                </h4>

                @if(!empty($endpoint['urlParameters']))
                    @include('scribe::themes.tailwind.parameters', ['parameters' => $endpoint['urlParameters'], 'title' => 'URL Parameters'])
                @endif

                @if(!empty($endpoint['queryParameters']))
                    @include('scribe::themes.tailwind.parameters', ['parameters' => $endpoint['queryParameters'], 'title' => 'Query Parameters'])
                @endif

                @if(!empty($endpoint['bodyParameters']))
                    @include('scribe::themes.tailwind.parameters', ['parameters' => $endpoint['bodyParameters'], 'title' => 'Body Parameters'])
                @endif
            </div>
        @endif

        <!-- Code Examples -->
        @if(!empty($endpoint['codeExamples']))
            <div class="mb-8">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    Example Request
                </h4>

                <!-- Language Tabs - Only define once! -->
                <div class="example-language-tabs flex space-x-1 mb-4">
                    @foreach(['bash', 'javascript', 'php', 'python'] as $lang)
                        <button class="example-language-tab px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200"
                                data-language="{{ $lang }}"
                                onclick="switchExampleLanguage('{{ $lang }}')">
                            {{ ucfirst($lang) }}
                        </button>
                    @endforeach
                </div>

                @foreach($endpoint['codeExamples'] as $example)
                    <div class="example-{{ $example['language'] }}" style="display: none;">
                        <div class="code-block relative">
                            <pre class="pt-12 pb-4 px-4 bg-gray-900 text-gray-100 rounded-lg overflow-x-auto"><code class="language-{{ $example['language'] }}">{{ $example['content'] }}</code></pre>
                            <button onclick="copyToClipboard(`{{ addslashes($example['content']) }}`)"
                                    class="absolute top-4 right-4 p-2 text-gray-400 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Response Examples -->
        @if(!empty($endpoint['responses']))
            <div class="mb-8">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Example Response
                </h4>

                @foreach($endpoint['responses'] as $response)
                    <div class="mb-4">
                        @if($response['status'])
                            <div class="flex items-center mb-2">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    @if($response['status'] >= 200 && $response['status'] < 300) bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700
                                    @elseif($response['status'] >= 400 && $response['status'] < 500) bg-red-100 text-red-800 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700
                                    @else bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700
                                    @endif">
                                    {{ $response['status'] }}
                                </span>
                                @if(!empty($response['description']))
                                    <span class="ml-3 text-gray-600 dark:text-gray-400">{{ $response['description'] }}</span>
                                @endif
                            </div>
                        @endif

                        @if(!empty($response['content']))
                            <div class="code-block relative">
                                <pre class="pt-12 pb-4 px-4 bg-gray-900 text-gray-100 rounded-lg overflow-x-auto"><code class="language-json">{{ $response['content'] }}</code></pre>
                                <button onclick="copyToClipboard(`{{ addslashes($response['content']) }}`)"
                                        class="absolute top-4 right-4 p-2 text-gray-400 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Try it Out -->
        @if(config('scribe.try_it_out.enabled'))
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-2-8V6a2 2 0 012-2h4a2 2 0 012 2v2M3 20h18a1 1 0 001-1V9a1 1 0 00-1-1H3a1 1 0 00-1 1v10a1 1 0 001 1z"></path>
                    </svg>
                    Test this endpoint
                </h4>
                <p class="text-gray-600 dark:text-gray-400 mb-4">You can test this API endpoint right here. Make sure you have proper authentication if required.</p>

                <!-- Try it out form will be injected here by Scribe -->
                <div id="try-it-out-{{ $endpoint['fullSlug'] }}"></div>
            </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity';
        toast.textContent = 'Copied to clipboard!';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 2000);
    });
}
</script>
