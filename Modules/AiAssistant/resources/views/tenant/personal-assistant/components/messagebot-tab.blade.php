<div x-show="activeTab === 'option4'">
    <div class="border-slate-200 dark:border-slate-600">
        <div class="col-span-3 mt-3">
            <x-label for="personal_assistant_id" class="mt-[2px]" :value="t('Personal Assistant')" />

            <select wire:model="selectedAssistantId" id="personal_assistant_id" class="block w-full mt-1 tom-select">
                <option value="">{{ t('select_option') }}</option>
                @foreach ($personalAssistant as $assistant)
                    <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                @endforeach
            </select>

            <x-input-error for="selectedAssistantId" class="mt-2" />
        </div>
    </div>
</div>
