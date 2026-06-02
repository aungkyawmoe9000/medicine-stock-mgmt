<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Medicine Stock Management') }}
        </h2>
    </x-slot>

    <div class="mb-6">
        <livewire:dashboard.stat-cards />
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <livewire:admin.dashboard-insights />
    </div>
</x-app-layout>
