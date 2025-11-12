@if(!empty($group['description']))
<div class="mb-8 p-6 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl border border-primary-200 dark:border-primary-700">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 flex items-center">
        <div class="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center mr-3">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        {{ $group['name'] }}
    </h2>
    <div class="prose dark:prose-invert max-w-none">
        {!! Parsedown::instance()->text($group['description']) !!}
    </div>
</div>
@endif

@foreach($group['endpoints'] as $endpoint)
    @include('scribe::themes.tailwind.endpoint', ['endpoint' => $endpoint])
@endforeach
