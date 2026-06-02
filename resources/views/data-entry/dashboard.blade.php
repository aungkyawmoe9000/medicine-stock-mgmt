<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Entry Control Panel') }}
        </h2>
    </x-slot>


    <div class="mb-6">
        <livewire:dashboard.stat-cards />
    </div>
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <livewire:admin.dashboard-insights />
    </div>
    <div class="mb-6 bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
        <h3 class="text-lg font-bold text-gray-800">Welcome, {{ auth()->user()->name }}!</h3>
        <p class="text-gray-600 mt-1">ယနေ့ ဆေးဝါး အဝင်/အထွက် စာရင်းများကို အောက်ပါ ခလုတ်များမှတဆင့် လွယ်ကူစွာ စတင် မှတ်တမ်းတင်နိုင်ပါသည်။</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
            </div>
            <h4 class="text-2xl font-bold text-gray-800">Stock IN</h4>
            <p class="text-sm text-gray-500 mt-2 mb-6">ဆေးဝါးအသစ်များ၊ Grant များမှ ရရှိသော ဆေးများ သွင်းရန်</p>
            <a href="{{ route('data-entry.stock-in') }}" wire:navigate class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-8 rounded-lg w-full transition text-center">
                + အဝင်စာရင်းသွင်းမည်
            </a>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
            </div>
            <h4 class="text-2xl font-bold text-gray-800">Stock OUT</h4>
            <p class="text-sm text-gray-500 mt-2 mb-6">စီမံကိန်းများ သို့မဟုတ် ဆေးခန်းများသို့ ဆေးဝါးများ ထုတ်ပေးရန်</p>
            <a href="{{ route('data-entry.stock-out') }}" wire:navigate class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-8 rounded-lg w-full transition text-center">
                - အထွက်စာရင်းသွင်းမည်
            </a>
        </div>
    </div>
</x-app-layout>
