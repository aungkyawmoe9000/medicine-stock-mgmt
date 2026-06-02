<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Grant;
use App\Models\Project;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\MonthlyStockBalance;
use App\Models\User;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Transaction မှတ်တမ်းတင်ရန် User တစ်ဦး ယူပါမည် (စနစ်ထဲတွင် ရှိပြီးသား Admin သို့မဟုတ် ပထမဆုံး User)
        $user = User::where('name', 'woisa-data-entry')->first();
        if (!$user) {
            $user = User::factory()->create(['name' => 'woisa-data-entry1', 'password' => '4321W0isa', 'email' => 'test@example.com', 'role' => 'data-entry']);
        }

        // 2. နေရာဒေသများ (Locations)
        $loc1 = Location::create(['location' => 'Main Warehouse', 'office' => 'Headquarter']);
        $loc2 = Location::create(['location' => 'Clinic A (North)', 'office' => 'Branch 1']);

        // 3. ရန်ပုံငွေများ (Grants)
        $grant1 = Grant::create(['grant' => 'UNICEF-2026']);
        $grant2 = Grant::create(['grant' => 'Global Fund-TB']);

        // 4. စီမံကိန်းများ (Projects)
        $proj1 = Project::create(['project' => 'Malaria Control Project', 'project_code' => 'PC-012']);
        $proj2 = Project::create(['project' => 'Maternal & Child Health', 'project_code' => 'PC-013']);

        // 5. ဆေးဝါးများ (Items)
        $item1 = Item::create(['item' => 'Amoxicillin 500mg', 'unit' => 'Capsules', 'min_stock_level' => 1000]);
        $item2 = Item::create(['item' => 'Paracetamol 500mg', 'unit' => 'Tablets', 'min_stock_level' => 5000]);
        $item3 = Item::create(['item' => 'Surgical Masks', 'unit' => 'Boxes', 'min_stock_level' => 50]);

        // 6. အဝင်/အထွက် မှတ်တမ်းများ (Transactions)
        $today = Carbon::now();

        // -- Stock In (အဝင်များ) --
        Transaction::create([
            't_type' => 'In',
            'item_id' => $item1->id,
            'brand' => 'Amoxil',
            'location_id' => $loc1->id,
            'grant_id' => $grant1->id,
            'project_id' => $proj1->id,
            'batch' => 'BATCH-A001',
            'qty' => 5000,
            'expire_date' => $today->copy()->addYears(2),
            'user_id' => $user->id,
        ]);

        Transaction::create([
            't_type' => 'In',
            'item_id' => $item2->id,
            'brand' => 'Tylenol',
            'location_id' => $loc1->id,
            'grant_id' => $grant2->id,
            'project_id' => $proj2->id,
            'batch' => 'BATCH-P992',
            'qty' => 10000,
            'expire_date' => $today->copy()->addYears(3),
            'user_id' => $user->id,
        ]);

        // -- Stock Out (အထွက်များ) --
        Transaction::create([
            't_type' => 'Out',
            'item_id' => $item1->id,
            'location_id' => $loc2->id, // Clinic A သို့ ထုတ်ပေးသည်
            'project_id' => $proj1->id,
            'qty' => 1000, // ၅၀၀၀ ထဲမှ ၁၀၀၀ ထုတ်သည်
            'user_id' => $user->id,
        ]);

        // 7. လစဉ် လက်ကျန်စာရင်းများ (Monthly Stock Balances - Chart ဆွဲရန် အသုံးဝင်မည်)
        MonthlyStockBalance::create([
            'item_id' => $item1->id,
            'year' => $today->year,
            'month' => $today->month,
            'opening_balance' => 0,
            'total_stock_in' => 5000,
            'total_stock_out' => 1000,
            'closing_balance' => 4000,
        ]);

        MonthlyStockBalance::create([
            'item_id' => $item2->id,
            'year' => $today->year,
            'month' => $today->month,
            'opening_balance' => 0,
            'total_stock_in' => 10000,
            'total_stock_out' => 0,
            'closing_balance' => 10000,
        ]);
    }
}
