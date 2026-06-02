<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Stock IN (ဆေးဝါးအဝင်စာရင်း)') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:data-entry.stock-in-form />
        </div>
    </div>
</x-app-layout>
