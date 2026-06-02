<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2 text-red-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
            <h2 class="font-semibold text-xl leading-tight">
                {{ __('Stock OUT (ဆေးဝါးအထွက်စာရင်း / ထုတ်ပေးခြင်း)') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:data-entry.stock-out-form />
        </div>
    </div>
</x-app-layout>
