<div class="mb-6">
    <h5 class="text-md font-semibold text-gray-900 dark:text-white mb-3">{{ $title }}</h5>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Required</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($parameters as $parameter)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <code class="text-sm font-mono text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded">
                            {{ $parameter['name'] }}
                        </code>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            {{ $parameter['type'] ?? 'string' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if(isset($parameter['required']) && $parameter['required'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400">
                                Required
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                Optional
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900 dark:text-white">
                            @if(!empty($parameter['description']))
                                {!! Parsedown::instance()->text($parameter['description']) !!}
                            @else
                                <span class="text-gray-500 dark:text-gray-400 italic">No description</span>
                            @endif
                        </div>
                        @if(!empty($parameter['example']))
                            <div class="mt-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Example:</span>
                                <code class="ml-2 text-xs bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 rounded">
                                    {{ $parameter['example'] }}
                                </code>
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
