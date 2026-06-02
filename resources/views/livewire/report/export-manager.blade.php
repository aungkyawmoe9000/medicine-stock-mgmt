<?php

use Livewire\Volt\Component;
use App\Models\Grant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $report_date = '';
    public string $grant_id = '';
    public $grants = [];

    public function mount()
    {
        // Default အနေဖြင့် ယနေ့ရက်စွဲကို ထည့်ထားမည်
        $this->report_date = Carbon::today()->toDateString();
        $this->grants = Grant::orderBy('grant', 'asc')->get();
    }

    public function exportCsv()
    {
        $this->validate([
            'report_date' => 'required|date',
            'grant_id' => 'nullable|exists:grants,id',
        ]);

        $targetDate = Carbon::parse($this->report_date)->endOfDay();
        $now = Carbon::now();

        // ၁။ ရွေးချယ်ထားသော ရက်စွဲအထိ ရှိခဲ့သော အဝင် (In) စာရင်းများကို Grouping လုပ်၍ ဆွဲထုတ်ခြင်း
        $lotsQuery = Transaction::where('t_type', 'In')
            ->where('created_at', '<=', $targetDate);

        if (!empty($this->grant_id)) {
            $lotsQuery->where('grant_id', $this->grant_id);
        }

        $lots = $lotsQuery->select('item_id', 'brand', 'batch', 'expire_date', 'location_id', 'project_id', DB::raw('SUM(qty) as total_in'))
            ->groupBy('item_id', 'brand', 'batch', 'expire_date', 'location_id', 'project_id')
            ->with(['item', 'location', 'project'])
            ->get();

        // ၂။ ဆေးတစ်မျိုးချင်းစီ၏ အထွက် (Out) စာရင်းများကို နှုတ်၍ လက်ကျန်နှင့် Consumption တွက်ချက်ခြင်း
        foreach ($lots as $lot) {
            // သတ်မှတ်ရက်အထိ ဖြစ်ပွားခဲ့သော အထွက်စာရင်း
            $outQuery = Transaction::where('t_type', 'Out')
                ->where('item_id', $lot->item_id)
                ->where('created_at', '<=', $targetDate);

            if ($lot->batch) { $outQuery->where('batch', $lot->batch); }
            if ($lot->location_id) { $outQuery->where('location_id', $lot->location_id); }
            if ($lot->project_id) { $outQuery->where('project_id', $lot->project_id); }

            $totalOut = $outQuery->sum('qty');

            // Current Stock Balance (ရွေးချယ်ထားသော ရက်စွဲအထိ လက်ကျန်)
            $lot->current_balance = max(0, $lot->total_in - $totalOut);

            // Monthly Average Consumption (နောက်ဆုံး ၃ လအတွင်း ပျမ်းမျှသုံးစွဲမှု)
            $threeMonthsAgo = $targetDate->copy()->subMonths(3);
            $consumptionQuery = Transaction::where('t_type', 'Out')
                ->where('item_id', $lot->item_id)
                ->whereBetween('created_at', [$threeMonthsAgo, $targetDate]);

            if ($lot->batch) { $consumptionQuery->where('batch', $lot->batch); }

            $totalConsumed = $consumptionQuery->sum('qty');
            $lot->avg_consumption = round($totalConsumed / 3, 2);

            // Will Expire at (သက်တမ်းကုန်ဆုံးရန် ကျန်ရှိသည့် အခြေအနေ)
            if ($lot->expire_date) {
                $expiry = Carbon::parse($lot->expire_date);
                if ($expiry->isPast()) {
                    $lot->will_expire_at = 'Expired';
                } else {
                    $daysRemaining = $now->diffInDays($expiry, false);
                    $monthsRemaining = round($daysRemaining / 30);
                    $lot->will_expire_at = $monthsRemaining <= 0 ? 'Expires this month' : "In {$monthsRemaining} Months";
                }
            } else {
                $lot->will_expire_at = 'N/A';
            }
        }

        // ၃။ CSV ဖိုင်အဖြစ် Stream Response ထုတ်ပေးခြင်း
        $fileName = 'medicine_stock_report_' . Carbon::parse($this->report_date)->format('Ymd') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($lots) {
            $file = fopen('php://output', 'w');

            // Excel တွင် မြန်မာစာသား/အက္ခရာများ မပျက်စေရန် BOM ထည့်သွင်းခြင်း
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // သတ်မှတ်ထားသော Header တိုင်များ
            fputcsv($file, [
                'Items', 'Brand', 'Unit', 'Expire Date', 'Location',
                'Project Code', 'Current Stock Balance', 'Monthly Average Consumption',
                'Batch', 'Will Expire at'
            ]);

            foreach ($lots as $lot) {
                fputcsv($file, [
                    $lot->item->item ?? 'Unknown Item',
                    $lot->brand ?: 'N/A',
                    $lot->item->unit ?? 'N/A',
                    $lot->expire_date ? Carbon::parse($lot->expire_date)->format('d-M-Y') : 'N/A',
                    $lot->location->location ?? 'Main Store',
                    $lot->project->project_code ?? 'N/A',
                    $lot->current_balance,
                    $lot->avg_consumption,
                    $lot->batch ?: 'N/A',
                    $lot->will_expire_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}; ?>

<div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
    <div class="mb-6 flex items-center space-x-3 border-b pb-4">
        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l3-3m-3 3l-3-3m2-8e7a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-800">Export Stock Report</h3>
            <p class="text-xs text-gray-500">ရက်စွဲနှင့် ရန်ပုံငွေအလိုက် စစ်ထုတ်ပြီး စာရင်းများကို .csv ဖိုင်အဖြစ် ထုတ်ယူပါ။</p>
        </div>
    </div>

    <form wire:submit="exportCsv" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Report Date (ဒီရက်စွဲအထိရှိသောစာရင်း) <span class="text-red-500">*</span></label>
                <input type="date" wire:model="report_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('report_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grant / Donor (ရန်ပုံငွေအဖွဲ့အစည်း)</label>
                <select wire:model="grant_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- All Grants (အားလုံးထုတ်မည်) --</option>
                    @foreach($grants as $g)
                        <option value="{{ $g->id }}">{{ $g->grant }}</option>
                    @endforeach
                </select>
                @error('grant_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition shadow-sm flex items-center space-x-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Generate & Download CSV</span>
            </button>
        </div>
    </form>
</div>
