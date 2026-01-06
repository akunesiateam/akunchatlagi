@props(['disabled' => false])

<div x-data="{
    isLoading: false,
    init() {
        Livewire.on('contacts-updated', () => {
            setTimeout(() => {
                this.isLoading = false;
            }, 500);
        });
    }
}">
    <select {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' =>
                'no-arrow block w-full border-gray-300 rounded-md shadow-sm text-slate-900 sm:text-sm
                focus:ring-gray-400 focus:border-gray-400 disabled:opacity-50
                dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500
                dark:text-slate-200 dark:focus:ring-gray-500 dark:focus:border-gray-500',
        ]) !!}>
        {{ $slot }}
    </select>
</div>

<style>
/* HIDE NATIVE ARROW */
.no-arrow {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-image: none !important;
}
option {
  background-color: #fff;
  color: #333;
  padding: 5px;
}

option:hover {
  background-color: #e0e0e0;
}
</style>
