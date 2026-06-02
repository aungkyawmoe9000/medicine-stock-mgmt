<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Location;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\MonthlyStockBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Form Inputs
    public $editingTransactionId = null;
    public $item_id = '';
    public $location_id = '';
    public $project_id = '';
    public $batch = '';
    public $qty = '';

    // Master Data Collections & Recent Records
    public $items, $locations, $projects;
    public $recentTransactions = [];

    // Dynamic Data for Selected Item
    public $available_balance = 0;
    public $available_batches = [];

    public function mount()
    {
        $this->items = Item::orderBy('item', 'asc')->get();
        $this->locations = Location::orderBy('location', 'asc')->get();
        $this->projects = Project::orderBy('project', 'asc')->get();
        $this->loadRecentTransactions();
    }

    public function loadRecentTransactions()
    {
        $this->recentTransactions = Transaction::with(['item', 'location', 'project', 'user'])
            ->where('t_type', 'Out')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    // လက်ကျန်နှင့် Batch များကို ဆွဲထုတ်သည့် Helper Function
    private function fetchItemDetails($itemId)
    {
        $this->available_balance = 0;
        $this->available_batches = [];

        if (!empty($itemId)) {
            $now = Carbon::now();

            $currentBalance = MonthlyStockBalance::where('item_id', $itemId)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->first();

            if (!$currentBalance) {
                $lastBalance = MonthlyStockBalance::where('item_id', $itemId)
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->first();
                $this->available_balance = $lastBalance ? $lastBalance->closing_balance : 0;
            } else {
                $this->available_balance = $currentBalance->closing_balance;
            }

            $this->available_batches = Transaction::where('item_id', $itemId)
                ->where('t_type', 'In')
                ->whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->pluck('batch')
                ->toArray();
        }
    }

    public function updatedItemId($value)
    {
        $this->qty = '';
        $this->batch = '';
        $this->fetchItemDetails($value);
    }

    // နောက်ကျသော စာရင်းရှိ/မရှိ စစ်ဆေးသည့် Helper Function (In ရော Out ပါ စစ်သည်)
    private function hasSubsequentTransaction($itemId, $createdAt)
    {
        return Transaction::where('item_id', $itemId)
            ->where('created_at', '>', $createdAt)
            ->exists();
    }

    // Edit နှိပ်သည့်အခါ
    public function editTransaction($id)
    {
        $txn = Transaction::findOrFail($id);

        if ($this->hasSubsequentTransaction($txn->item_id, $txn->created_at)) {
            session()->flash('error', 'ဤအထွက်စာရင်း၏နောက်တွင် အခြား (အဝင်/အထွက်) စာရင်းများရှိနေသဖြင့် ပြင်ဆင်ခွင့်မပြုပါ။');
            return;
        }

        $this->editingTransactionId = $txn->id;
        $this->item_id = $txn->item_id;

        // Item Details ဆွဲထုတ်မည် (Input များ မပျောက်စေရန် updateItemId ကို တိုက်ရိုက်မခေါ်ပါ)
        $this->fetchItemDetails($txn->item_id);

        $this->qty = $txn->qty;
        $this->batch = $txn->batch;
        $this->location_id = $txn->location_id;
        $this->project_id = $txn->project_id;
    }

    // Delete နှိပ်သည့်အခါ
    public function deleteTransaction($id)
    {
        $txn = Transaction::findOrFail($id);

        if ($this->hasSubsequentTransaction($txn->item_id, $txn->created_at)) {
            session()->flash('error', 'ဤအထွက်စာရင်း၏နောက်တွင် အခြား (အဝင်/အထွက်) စာရင်းများရှိနေသဖြင့် ဖျက်ခွင့်မပြုပါ။');
            return;
        }

        DB::beginTransaction();
        try {
            $txnDate = Carbon::parse($txn->created_at);
            $balance = MonthlyStockBalance::where('item_id', $txn->item_id)
                ->where('year', $txnDate->year)
                ->where('month', $txnDate->month)
                ->first();

            // အထွက်စာရင်း ဖျက်လိုက်သဖြင့် Balance သို့ ပြန်ပေါင်းထည့်မည်
            if ($balance) {
                $balance->total_stock_out -= $txn->qty;
                $balance->closing_balance += $txn->qty;
                $balance->save();
            }

            $txn->delete();
            DB::commit();

            $this->loadRecentTransactions();
            session()->flash('success_message', 'စာရင်းအား အောင်မြင်စွာ ဖျက်သိမ်းပြီး Balance သို့ ပြန်လည်ပေါင်းထည့်ပေးပြီးပါပြီ။');
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'အမှားအယွင်းဖြစ်ပွားခဲ့ပါသည်: ' . $e->getMessage());
        }
    }

    public function save()
    {
        // Edit လုပ်နေချိန်ဖြစ်ပါက မူလထုတ်ထားသော အရေအတွက်ကို လက်ကျန်နှင့် ပြန်ပေါင်းပြီးမှ Validation စစ်ဆေးမည်
        $effective_balance = $this->available_balance;
        if ($this->editingTransactionId) {
            $oldTxn = Transaction::find($this->editingTransactionId);
            if ($oldTxn && $oldTxn->item_id == $this->item_id) {
                $effective_balance += $oldTxn->qty;
            }
        }

        $this->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|integer|min:1|max:' . max(1, (int)$effective_balance),
            'location_id' => 'nullable|exists:locations,id',
            'project_id' => 'nullable|exists:projects,id',
            'batch' => 'nullable|string|max:255',
        ]);

        if ($this->qty > $effective_balance) {
             $this->addError('qty', 'လက်ကျန်မလုံလောက်ပါ။ ပြင်ဆင်ထုတ်ပေးနိုင်သော အများဆုံးအရေအတွက်မှာ: ' . $effective_balance . ' သာဖြစ်ပါသည်။');
             return;
        }

        $now = Carbon::now();
        DB::beginTransaction();

        try {
            if ($this->editingTransactionId) {
                $txn = Transaction::findOrFail($this->editingTransactionId);

                // Double check for safety
                if ($this->hasSubsequentTransaction($txn->item_id, $txn->created_at)) {
                    throw new \Exception('ဤအထွက်စာရင်း၏နောက်တွင် အခြား (အဝင်/အထွက်) စာရင်းများရှိနေသဖြင့် ပြင်ဆင်ခွင့်မပြုပါ။');
                }

                $txnDate = Carbon::parse($txn->created_at);

                if ($txn->item_id == $this->item_id) {
                    $qtyDiff = $this->qty - $txn->qty;
                    $balance = MonthlyStockBalance::where('item_id', $this->item_id)->where('year', $txnDate->year)->where('month', $txnDate->month)->first();

                    if ($balance) {
                        $balance->total_stock_out += $qtyDiff;
                        $balance->closing_balance -= $qtyDiff;
                        $balance->save();
                    }
                } else {
                    // Item ပြောင်းသွားပါက အဟောင်းသို့ ပြန်ပေါင်းမည်
                    $oldBalance = MonthlyStockBalance::where('item_id', $txn->item_id)->where('year', $txnDate->year)->where('month', $txnDate->month)->first();
                    if ($oldBalance) {
                        $oldBalance->total_stock_out -= $txn->qty;
                        $oldBalance->closing_balance += $txn->qty;
                        $oldBalance->save();
                    }

                    // အသစ်မှ ပြန်နှုတ်မည်
                    $newBalance = MonthlyStockBalance::firstOrCreate(
                        ['item_id' => $this->item_id, 'year' => $txnDate->year, 'month' => $txnDate->month],
                        ['opening_balance' => $this->available_balance, 'total_stock_in' => 0, 'total_stock_out' => 0, 'closing_balance' => $this->available_balance]
                    );
                    $newBalance->total_stock_out += $this->qty;
                    $newBalance->closing_balance -= $this->qty;
                    $newBalance->save();
                }

                $txn->update([
                    'item_id' => $this->item_id,
                    'location_id' => $this->location_id ?: null,
                    'project_id' => $this->project_id ?: null,
                    'batch' => $this->batch,
                    'qty' => $this->qty,
                ]);

                session()->flash('success_message', 'စာရင်းကို အောင်မြင်စွာ ပြင်ဆင်ပြီး Balance သို့ အတိုး/အလျှော့ ညှိပေးပြီးပါပြီ။');

            } else {
                // အသစ်ထည့်ခြင်း Logic
                $currentBalance = MonthlyStockBalance::where('item_id', $this->item_id)->where('year', $now->year)->where('month', $now->month)->first();

                if (!$currentBalance) {
                    $currentBalance = MonthlyStockBalance::create([
                        'item_id' => $this->item_id,
                        'year' => $now->year,
                        'month' => $now->month,
                        'opening_balance' => $this->available_balance,
                        'total_stock_in' => 0,
                        'total_stock_out' => 0,
                        'closing_balance' => $this->available_balance
                    ]);
                }

                Transaction::create([
                    't_type' => 'Out',
                    'item_id' => $this->item_id,
                    'location_id' => $this->location_id ?: null,
                    'project_id' => $this->project_id ?: null,
                    'batch' => $this->batch,
                    'qty' => $this->qty,
                    'user_id' => auth()->id(),
                ]);

                $currentBalance->total_stock_out += $this->qty;
                $currentBalance->closing_balance -= $this->qty;
                $currentBalance->save();

                session()->flash('success_message', 'ဆေးထုတ်ပေးခြင်း အောင်မြင်ပါသည်။ ဇယားတွင် စစ်ဆေးနိုင်ပါသည်။');
            }

            DB::commit();

            $savedItemId = $this->item_id;
            $this->resetForm();
            $this->item_id = $savedItemId;
            $this->updatedItemId($savedItemId);

            $this->loadRecentTransactions();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset(['editingTransactionId', 'item_id', 'location_id', 'project_id', 'batch', 'qty', 'available_balance', 'available_batches']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="bg-white p-8 rounded-xl shadow-sm border {{ $editingTransactionId ? 'border-orange-100 border-t-4 border-t-orange-500' : 'border-red-100 border-t-4 border-t-red-500' }}">

        <h3 class="text-lg font-bold mb-6 {{ $editingTransactionId ? 'text-orange-600' : 'text-red-600' }} border-b pb-2">
            {{ $editingTransactionId ? '✎ Edit Stock-Out (ထုတ်ပေးမှုစာရင်း ပြင်ဆင်ရန်)' : '- Add New Stock-Out (ဆေးထုတ်ပေးမည်)' }}
        </h3>

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine / Item to Dispatch <span class="text-red-500">*</span></label>
                    <select wire:model.live="item_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm bg-red-50">
                        <option value="">-- Select an Item --</option>
                        @foreach($items as $itm)
                            <option value="{{ $itm->id }}">{{ $itm->item }} ({{ $itm->unit }})</option>
                        @endforeach
                    </select>
                    @error('item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dispatch Quantity <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" wire:model="qty" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm {{ $item_id == '' ? 'bg-gray-100 cursor-not-allowed' : '' }}" placeholder="e.g. 1000" {{ $item_id == '' ? 'disabled' : '' }}>
                    </div>

                    @if($item_id != '')
                        <div class="mt-1 flex justify-between items-center text-xs">
                            <span class="{{ $qty > ($available_balance + ($editingTransactionId ? App\Models\Transaction::find($editingTransactionId)?->qty : 0)) ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                ရရှိနိုင်သော လက်ကျန် (Available):
                                <strong>
                                    {{ number_format($available_balance + ($editingTransactionId && App\Models\Transaction::find($editingTransactionId)?->item_id == $item_id ? App\Models\Transaction::find($editingTransactionId)?->qty : 0)) }}
                                </strong>
                            </span>
                        </div>
                    @endif
                    @error('qty') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number (Optional)</label>
                    @if(empty($available_batches))
                        <input type="text" wire:model="batch" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm bg-gray-50" placeholder="No Batch Available" disabled>
                    @else
                        <select wire:model="batch" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                            <option value="">-- Select Batch --</option>
                            @foreach($available_batches as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('batch') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                    <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Destination Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dispatch To (Location/Clinic)</label>
                            <select wire:model="location_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">-- None --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->location }} ({{ $loc->office }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">For Project</label>
                            <select wire:model="project_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">-- None --</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->project }} ({{ $proj->project_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 flex space-x-3 items-center">
                <button type="submit" class="{{ $editingTransactionId ? 'bg-orange-500 hover:bg-orange-600' : 'bg-red-600 hover:bg-red-700' }} text-white font-bold py-2.5 px-6 rounded-lg transition shadow-sm flex items-center" {{ $item_id == '' ? 'disabled' : '' }}>
                    @if($editingTransactionId)
                        ✓ Update Dispatch
                    @else
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                        Confirm Dispatch
                    @endif
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
                Recently Dispatched (နောက်ဆုံးထုတ်ပေးမှု ၅ ခု)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Item Details</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Destination/Project</th>
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
                                <div class="text-xs text-gray-500">Batch: {{ $txn->batch ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                -{{ number_format($txn->qty) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $txn->location->location ?? 'No Location' }}</div>
                                <div class="text-xs mt-1 text-gray-400">{{ $txn->project->project_code ?? 'No Project' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="editTransaction({{ $txn->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="deleteTransaction({{ $txn->id }})" wire:confirm="သေချာပါသလား? ဤစာရင်းအားဖျက်လိုက်ပါက လက်ကျန် (Balance) သို့ ပြန်လည်ပေါင်းထည့်သွားမည်ဖြစ်ပါသည်။" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                No recent dispatch records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
