<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-900">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-2">Welcome, System Administrator!</h3>
                    <p>သင်သည် Admin ဖြစ်သောကြောင့် System တစ်ခုလုံးနှင့် ဆေးဝါးအချက်အလက် အားလုံးကို အပြည့်အဝ ထိန်းချုပ်ခွင့် ရှိပါသည်။</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
