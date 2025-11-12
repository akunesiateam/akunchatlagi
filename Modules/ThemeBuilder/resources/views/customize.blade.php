<x-app-layout>
     <x-slot:title>
        {{ t('create_theme') }}
    </x-slot:title>
     <div class="mx-auto h-full">
        <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full">
                <div id="grapes-editor" class="w-full">
                    <grapes-editor :theme-id="{{ $theme->id }}"></grapes-editor>
                </div>
            </div>
        </div>
    </div>
   
</x-app-layout>
