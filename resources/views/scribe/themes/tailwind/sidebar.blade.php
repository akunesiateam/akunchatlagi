<div class="jets-search">
    @foreach($groups as $group)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 uppercase tracking-wider">
                {{ $group['name'] }}
            </h3>
            
            @foreach($group['endpoints'] as $endpoint)
                @php
                    $method = strtoupper($endpoint['httpMethods'][0]);
                    $methodColors = [
                        'GET' => 'bg-green-500',
                        'POST' => 'bg-blue-500',
                        'PUT' => 'bg-yellow-500',
                        'PATCH' => 'bg-purple-500',
                        'DELETE' => 'bg-red-500',
                    ];
                    $methodColor = $methodColors[$method] ?? 'bg-gray-500';
                @endphp
                
                <a href="#{{ $endpoint['fullSlug'] }}" 
                   class="flex items-center px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                    <span class="w-2 h-2 rounded-full {{ $methodColor }} mr-3 flex-shrink-0"></span>
                    <span class="text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white font-medium truncate">
                        {{ $endpoint['title'] }}
                    </span>
                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 font-mono">
                        {{ $method }}
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach
</div>
