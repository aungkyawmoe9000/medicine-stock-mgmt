<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\MonthlyStockBalance;
use Carbon\Carbon;

new class extends Component {
    public string $filter = 'Under min_stock_level';
    public $selectedItemId = ''; // Chart အတွက် ရွေးချယ်ထားသော Item ID
    public $allItems = [];

    public function mount()
    {
        // Item အားလုံးကို Dropdown အတွက် ဆွဲထုတ်ထားမည်
        $this->allItems = Item::orderBy('item', 'asc')->get();
    }

    public function with(): array
    {
        return [
            'tableData' => $this->getTableData(),
            'chartData' => $this->getChartData(),
        ];
    }

    private function getChartData(): array
    {
        if ($this->filter !== 'Consumption Trend') {
            return [];
        }

        $now = Carbon::now();
        $months = [];
        $quantities = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $months[] = $date->format('M Y');

            // Stock Out ဖြစ်သော Transaction များကို လအလိုက်ဆွဲထုတ်ခြင်း
            $query = Transaction::where('t_type', 'Out')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            // Item တစ်ခုကို ရွေးချယ်ထားပါက ၎င်း Item အတွက်သာ Filter လုပ်မည်
            if (!empty($this->selectedItemId)) {
                $query->where('item_id', $this->selectedItemId);
            }

            $quantities[] = $query->sum('qty');
        }

        // Chart Label တွင် ပြသရန် Item Name ကို ရှာဖွေခြင်း
        $selectedItemName = 'All Medicine Items';
        if (!empty($this->selectedItemId)) {
            $itm = Item::find($this->selectedItemId);
            $selectedItemName = $itm ? $itm->item : 'Unknown Item';
        }

        return [
            'labels' => $months,
            'data' => $quantities,
            'itemName' => $selectedItemName,
        ];
    }

    private function getTableData()
    {
        if ($this->filter === 'Consumption Trend') {
            return [];
        }

        $now = Carbon::now();

        if ($this->filter === 'Under min_stock_level') {
            return MonthlyStockBalance::with('item')
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->get()
                ->filter(fn($balance) => $balance->closing_balance < ($balance->item->min_stock_level ?? 0));
        }

        if ($this->filter === 'Expired Items') {
            return Transaction::with('item')
                ->where('t_type', 'In')
                ->whereNotNull('expire_date')
                ->where('expire_date', '<', $now)
                ->get();
        }

        if ($this->filter === 'Nearly Expire') {
            return Transaction::with('item')
                ->where('t_type', 'In')
                ->whereNotNull('expire_date')
                ->whereBetween('expire_date', [$now, $now->copy()->addMonths(6)])
                ->get();
        }

        return [];
    }
}; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4 space-y-4 md:space-y-0">
        <h3 class="text-lg font-bold text-gray-800">
            <svg class="w-5 h-5 inline-block mr-1 text-blue-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002-2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Stock Insights & Analysis
        </h3>

        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full md:w-auto">
            <select wire:model.live="filter" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 font-medium text-gray-700 bg-gray-50 py-2 pl-3 pr-10 border w-full sm:w-auto">
                <option value="Under min_stock_level">Under Minimum Stock Level</option>
                <option value="Expired Items">Expired Items</option>
                <option value="Nearly Expire">Nearly Expire</option>
                <option value="Consumption Trend">Consumption Trend (Chart)</option>
            </select>

            @if($filter === 'Consumption Trend')
                <select wire:model.live="selectedItemId" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 font-medium text-gray-700 bg-blue-50 py-2 pl-3 pr-10 border w-full sm:w-auto">
                    <option value="">All Items (Combined)</option>
                    @foreach($allItems as $itm)
                        <option value="{{ $itm->id }}">{{ $itm->item }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div>
        @if($filter === 'Consumption Trend')
            <div class="bg-white p-4 rounded-lg shadow-inner border border-gray-100"
                 x-data="{
                     chart: null,
                     initChart() {
                         let ctx = document.getElementById('consumptionChart').getContext('2d');
                         if (this.chart) this.chart.destroy();
                         this.chart = new Chart(ctx, {
                             type: 'line',
                             data: {
                                 labels: @js($chartData['labels'] ?? []),
                                 datasets: [{
                                     label: 'Monthly Consumption: ' + @js($chartData['itemName'] ?? 'All Items'),
                                     data: @js($chartData['data'] ?? []),
                                     borderColor: '#3b82f6',
                                     backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                     borderWidth: 2,
                                     fill: true,
                                     tension: 0.4
                                 }]
                             },
                             options: {
                                 responsive: true,
                                 maintainAspectRatio: false,
                             }
                         });
                     }
                 }"
                 x-init="initChart()"
                 x-effect="$nextTick(() => initChart())">
                <div class="relative h-72 w-full">
                    <canvas id="consumptionChart"></canvas>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tableData as $data)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $data->item->item ?? $data->item }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($filter === 'Under min_stock_level')
                                        Balance: <strong class="text-red-600">{{ $data->closing_balance }}</strong>
                                        (Min Req: {{ $data->item->min_stock_level }})
                                    @else
                                        Batch: {{ $data->batch ?? 'N/A' }} <br>
                                        Exp: <strong class="{{ $filter === 'Expired Items' ? 'text-red-600' : 'text-orange-600' }}">
                                            {{ $data->expire_date ? \Carbon\Carbon::parse($data->expire_date)->format('d M, Y') : 'N/A' }}
                                        </strong>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full
                                        {{ $filter === 'Under min_stock_level' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $filter === 'Expired Items' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $filter === 'Nearly Expire' ? 'bg-orange-100 text-orange-800' : '' }}
                                    ">
                                        {{ $filter }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    ဒီအမျိုးအစားထဲမှာ လက်ရှိ ဆေးဝါးမှတ်တမ်း (Data) မရှိသေးပါ။
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
