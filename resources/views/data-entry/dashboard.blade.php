<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Entry Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-600">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-2">Welcome, Data Entry User!</h3>
                    <p>သင်သည် ဆေးဝါး အဝင်/အထွက် စာရင်းများကို နေ့စဉ် မှတ်တမ်းတင်နိုင်ရန် ဤစာမျက်နှာမှတဆင့် လုပ်ဆောင်နိုင်ပါသည်။</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
