<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Location;
use App\Models\Grant;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\MonthlyStockBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Form Inputs
    public $editingTransactionId = null;
    public $item_id = '';
    public $brand = '';
    public $location_id = '';
    public $grant_id = '';
    public $project_id = '';
    public $batch = '';
    public $qty = '';
    public $expire_date = '';

    // Master Data & Recent Records
    public $items, $locations, $grants, $projects;
    public $recentTransactions = [];

    public function mount()
    {
        $this->items = Item::orderBy('item', 'asc')->get();
        $this->locations = Location::orderBy('location', 'asc')->get();
        $this->grants = Grant::orderBy('grant', 'asc')->get();
        $this->projects = Project::orderBy('project', 'asc')->get();
        $this->loadRecentTransactions();
    }

    public function loadRecentTransactions()
    {
        $this->recentTransactions = Transaction::with(['item', 'location', 'user'])
            ->where('t_type', 'In')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    // စာရင်းပြင်ရန် Edit ခလုတ်နှိပ်သည့်အခါ
    public function editTransaction($id)
    {
        $txn = Transaction::findOrFail($id);

        // စည်းမျဉ်း ၁ - ဤစာရင်းသွင်းပြီးနောက်ပိုင်း အဆိုပါ Item အား Stock Out ထုတ်ထားခြင်း ရှိ/မရှိ စစ်ဆေးမည်
        $hasSubsequentOut = Transaction::where('t_type', 'Out')
            ->where('item_id', $txn->item_id)
            ->where('created_at', '>', $txn->created_at)
            ->exists();

        if ($hasSubsequentOut) {
            session()->flash('error', 'ဤအဝင်စာရင်း၏နောက်တွင် ဆေးထုတ်ပေးမှု (Stock-Out) မှတ်တမ်းရှိနေသဖြင့် ပြင်ဆင်ခွင့်မပြုပါ။');
            return;
        }

        $this->editingTransactionId = $txn->id;
        $this->item_id = $txn->item_id;
        $this->qty = $txn->qty;
        $this->brand = $txn->brand;
        $this->batch = $txn->batch;
        $this->expire_date = $txn->expire_date ? Carbon::parse($txn->expire_date)->format('Y-m-d') : '';
        $this->location_id = $txn->location_id;
        $this->grant_id = $txn->grant_id;
        $this->project_id = $txn->project_id;
    }

    // စာရင်းဖျက်ရန် Delete ခလုတ်နှိပ်သည့်အခါ
    public function deleteTransaction($id)
    {
        $txn = Transaction::findOrFail($id);

        // စည်းမျဉ်း ၁ - ဖျက်ခွင့်မပြုမီ စစ်ဆေးခြင်း
        $hasSubsequentOut = Transaction::where('t_type', 'Out')
            ->where('item_id', $txn->item_id)
            ->where('created_at', '>', $txn->created_at)
            ->exists();

        if ($hasSubsequentOut) {
            session()->flash('error', 'ဤအဝင်စာရင်း၏နောက်တွင် ဆေးထုတ်ပေးမှု (Stock-Out) မှတ်တမ်းရှိနေသဖြင့် ဖျက်ခွင့်မပြုပါ။');
            return;
        }

        DB::beginTransaction();
        try {
            // စည်းမျဉ်း ၂ - ဖျက်လိုက်ပါက သက်ဆိုင်ရာ Balance မှ ပြန်လည်နှုတ်ယူခြင်း
            $txnDate = Carbon::parse($txn->created_at);
            $balance = MonthlyStockBalance::where('item_id', $txn->item_id)
                ->where('year', $txnDate->year)
                ->where('month', $txnDate->month)
                ->first();

            if ($balance) {
                $balance->total_stock_in -= $txn->qty;
                $balance->closing_balance -= $txn->qty;
                $balance->save();
            }

            $txn->delete();
            DB::commit();

            $this->loadRecentTransactions();
            session()->flash('success_message', 'စာရင်းအား အောင်မြင်စွာ ဖျက်သိမ်းပြီး Balance မှ ပြန်လည်နှုတ်ယူပြီးပါပြီ။');
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'အမှားအယွင်းဖြစ်ပွားခဲ့ပါသည်: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|integer|min:1',
            'location_id' => 'nullable|exists:locations,id',
            'grant_id' => 'nullable|exists:grants,id',
            'project_id' => 'nullable|exists:projects,id',
            'brand' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:255',
            'expire_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            if ($this->editingTransactionId) {
                // Edit Logic (ပြင်ဆင်ခြင်း)
                $txn = Transaction::findOrFail($this->editingTransactionId);

                // Double Check Validation
                $hasSubsequentOut = Transaction::where('t_type', 'Out')
                    ->where('item_id', $txn->item_id)
                    ->where('created_at', '>', $txn->created_at)
                    ->exists();

                if ($hasSubsequentOut) {
                    throw new \Exception('ဤအဝင်စာရင်း၏နောက်တွင် ဆေးထုတ်ပေးမှု (Stock-Out) မှတ်တမ်းရှိနေသဖြင့် ပြင်ဆင်၍မရတော့ပါ။');
                }

                $txnDate = Carbon::parse($txn->created_at);

                // Balance ပြန်လည်ညှိပေးခြင်း (စည်းမျဉ်း ၂)
                if ($txn->item_id == $this->item_id) {
                    // Item တူညီပါက အရေအတွက်ကွာခြားချက်ကိုသာ အတိုးအလျှော့လုပ်မည်
                    $qtyDiff = $this->qty - $txn->qty;
                    $balance = MonthlyStockBalance::where('item_id', $this->item_id)
                        ->where('year', $txnDate->year)
                        ->where('month', $txnDate->month)
                        ->first();

                    if ($balance) {
                        $balance->total_stock_in += $qtyDiff;
                        $balance->closing_balance += $qtyDiff;
                        $balance->save();
                    }
                } else {
                    // Item အမျိုးအစားပါ ပြောင်းလဲလိုက်ပါက၊ အဟောင်းမှနှုတ်၍ အသစ်တွင်သွားပေါင်းမည်
                    $oldBalance = MonthlyStockBalance::where('item_id', $txn->item_id)->where('year', $txnDate->year)->where('month', $txnDate->month)->first();
                    if ($oldBalance) {
                        $oldBalance->total_stock_in -= $txn->qty;
                        $oldBalance->closing_balance -= $txn->qty;
                        $oldBalance->save();
                    }

                    $newBalance = MonthlyStockBalance::firstOrCreate(
                        ['item_id' => $this->item_id, 'year' => $txnDate->year, 'month' => $txnDate->month],
                        ['opening_balance' => 0, 'total_stock_in' => 0, 'total_stock_out' => 0, 'closing_balance' => 0]
                    );
                    $newBalance->total_stock_in += $this->qty;
                    $newBalance->closing_balance += $this->qty;
                    $newBalance->save();
                }

                // Transaction အချက်အလက်များကို Update လုပ်ခြင်း
                $txn->update([
                    'item_id' => $this->item_id,
                    'brand' => $this->brand,
                    'location_id' => $this->location_id ?: null,
                    'grant_id' => $this->grant_id ?: null,
                    'project_id' => $this->project_id ?: null,
                    'batch' => $this->batch,
                    'qty' => $this->qty,
                    'expire_date' => $this->expire_date ?: null,
                ]);

                session()->flash('success_message', 'စာရင်းကို အောင်မြင်စွာ ပြင်ဆင်ပြီး Balance သို့ အတိုး/အလျှော့ ညှိပေးပြီးပါပြီ။');

            } else {
                // Create Logic (အသစ်ထည့်ခြင်း - မူလအတိုင်း)
                Transaction::create([
                    't_type' => 'In',
                    'item_id' => $this->item_id,
                    'brand' => $this->brand,
                    'location_id' => $this->location_id ?: null,
                    'grant_id' => $this->grant_id ?: null,
                    'project_id' => $this->project_id ?: null,
                    'batch' => $this->batch,
                    'qty' => $this->qty,
                    'expire_date' => $this->expire_date ?: null,
                    'user_id' => auth()->id(),
                ]);

                $now = Carbon::now();
                $lastMonth = $now->copy()->subMonth();

                $lastMonthBalance = MonthlyStockBalance::where('item_id', $this->item_id)
                    ->where('year', $lastMonth->year)
                    ->where('month', $lastMonth->month)
                    ->first();

                $openingBalance = $lastMonthBalance ? $lastMonthBalance->closing_balance : 0;

                $currentBalance = MonthlyStockBalance::firstOrCreate(
                    ['item_id' => $this->item_id, 'year' => $now->year, 'month' => $now->month],
                    [
                        'opening_balance' => $openingBalance,
                        'total_stock_in' => 0,
                        'total_stock_out' => 0,
                        'closing_balance' => $openingBalance
                    ]
                );

                $currentBalance->total_stock_in += $this->qty;
                $currentBalance->closing_balance += $this->qty;
                $currentBalance->save();

                session()->flash('success_message', 'အဝင်စာရင်းအသစ်ကို အောင်မြင်စွာ မှတ်တမ်းတင်ပြီးပါပြီ။');
            }

            DB::commit();
            $this->resetForm();
            $this->loadRecentTransactions();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'အမှားအယွင်းဖြစ်ပွားခဲ့ပါသည်: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset(['editingTransactionId', 'item_id', 'brand', 'location_id', 'grant_id', 'project_id', 'batch', 'qty', 'expire_date']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="bg-white p-8 rounded-xl shadow-sm border {{ $editingTransactionId ? 'border-orange-100 border-t-4 border-t-orange-500' : 'border-gray-100 border-t-4 border-t-green-500' }}">

        <h3 class="text-lg font-bold mb-6 {{ $editingTransactionId ? 'text-orange-600' : 'text-green-600' }} border-b pb-2">
            {{ $editingTransactionId ? '✎ Edit Stock-In (စာရင်းပြင်ဆင်ရန်)' : '+ Add New Stock-In (အဝင်စာရင်းသွင်းရန်)' }}
        </h3>

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg font-medium">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine / Item <span class="text-red-500">*</span></label>
                    <select wire:model="item_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        <option value="">-- Select an Item --</option>
                        @foreach($items as $itm)
                            <option value="{{ $itm->id }}">{{ $itm->item }} ({{ $itm->unit }})</option>
                        @endforeach
                    </select>
                    @error('item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Received Quantity <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="qty" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" placeholder="e.g. 5000">
                    @error('qty') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name (Optional)</label>
                    <input type="text" wire:model="brand" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" placeholder="e.g. Amoxil">
                    @error('brand') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number (Optional)</label>
                    <input type="text" wire:model="batch" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" placeholder="e.g. BATCH-001">
                    @error('batch') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expire Date (Optional)</label>
                    <input type="date" wire:model="expire_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                    @error('expire_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                    <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Additional Details (Optional)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Store Location</label>
                            <select wire:model="location_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">-- None --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->location }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Received from Grant</label>
                            <select wire:model="grant_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">-- None --</option>
                                @foreach($grants as $grant)
                                    <option value="{{ $grant->id }}">{{ $grant->grant }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Belongs to Project</label>
                            <select wire:model="project_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">-- None --</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->project_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 flex space-x-3 items-center">
                <button type="submit" class="{{ $editingTransactionId ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }} text-white font-bold py-2.5 px-6 rounded-lg transition shadow-sm">
                    {{ $editingTransactionId ? '✓ Update Stock In' : '+ Save Stock In' }}
                </button>
                <button type="button" wire:click="resetForm" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2.5 px-6 rounded-lg transition">
                    {{ $editingTransactionId ? 'Cancel Edit' : 'Clear Form' }}
                </button>

                @if (session()->has('success_message'))
                    <span class="text-green-600 text-sm font-semibold ml-4 animate-pulse">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('success_message') }}
                    </span>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                <svg class="w-4 h-4 inline-block mr-1 text-gray-500 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Recently Added Stock-In (နောက်ဆုံးထည့်သွင်းမှု ၅ ခု)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Item Details</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Location/Batch</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentTransactions as $txn)
                        <tr class="hover:bg-gray-50 transition {{ $editingTransactionId == $txn->id ? 'bg-orange-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($txn->created_at)->format('d M, Y') }}</div>
                                <div class="text-xs">{{ \Carbon\Carbon::parse($txn->created_at)->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="font-bold text-gray-900">{{ $txn->item->item ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $txn->brand ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                +{{ number_format($txn->qty) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $txn->location->location ?? 'No Location' }}</div>
                                <div class="text-xs font-mono bg-gray-100 px-1 py-0.5 rounded inline-block mt-1">{{ $txn->batch ?: 'No Batch' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="editTransaction({{ $txn->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="deleteTransaction({{ $txn->id }})" wire:confirm="သေချာပါသလား? ဤစာရင်းအားဖျက်လိုက်ပါက လက်ကျန် (Balance) မှ ပြန်လည်နှုတ်ယူသွားမည်ဖြစ်ပါသည်။" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                No recent stock-in records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
