@php
    $hasError = $errors->hasAny(['personal_assistant']) ? 'true' : 'false';
@endphp

<button 
    type="button" 
    x-on:click="activeTab = 'option4'"
    :class="{
        'border-b-2 border-primary-500 text-primary-500 dark:text-primary-500': activeTab === 'option4',
        'border-b-2 border-danger-500 text-danger-600': activeTab !== 'option4' && {{ $hasError }}
    }"
    class="px-4 py-2 text-sm font-medium dark:text-slate-300 hover:text-primary-500">
    {{ t('personal_assistant') }}
</button>
