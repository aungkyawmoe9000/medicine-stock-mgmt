<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\MonthlyStockBalance;
use Carbon\Carbon;

new class extends Component {
    public function with(): array
    {
        $now = Carbon::now();

        return [
            // ၁။ ဆေးပစ္စည်း စုစုပေါင်း
            'totalItems' => Item::count(),

            // ၂။ လက်ရှိလအတွင်း သတ်မှတ် Minimum Level အောက် ရောက်နေသော ပစ္စည်းအရေအတွက်
            'lowStockCount' => MonthlyStockBalance::where('year', $now->year)
                ->where('month', $now->month)
                ->get()
                ->filter(fn($balance) => $balance->closing_balance < ($balance->item->min_stock_level ?? 0))
                ->count(),

            // ၃။ နောက် ၆ လအတွင်း သက်တမ်းကုန်မည့် အဝင်စာရင်းများ
            'expiryAlertCount' => Transaction::where('t_type', 'In')
                ->whereNotNull('expire_date')
                ->where('expire_date', '>=', $now)
                ->where('expire_date', '<=', $now->copy()->addMonths(6))
                ->count(),
        ];
    }
}; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-blue-100 text-blue-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Total Items</p>
            <h4 class="text-2xl font-bold text-gray-800">{{ number_format($totalItems) }}</h4>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-red-100 text-red-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Low Stock Alerts</p>
            <h4 class="text-2xl font-bold text-gray-800">{{ number_format($lowStockCount) }}</h4>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-orange-100 text-orange-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Expiry Alerts (6Mo)</p>
            <h4 class="text-2xl font-bold text-gray-800">{{ number_format($expiryAlertCount) }}</h4>
        </div>
    </div>
</div>
