<?php

use Livewire\Volt\Component;
use App\Models\Item;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination; // Item တွေများလာရင် အဆင်ပြေအောင် Pagination ထည့်ထားပါတယ်

    public $itemId = null;
    public string $item = '';
    public string $unit = '';
    public ?int $min_stock_level = null;
    public bool $isEditMode = false;

    // Search Box အတွက်
    public string $search = '';

    public function with(): array
    {
        return [
            'items' => Item::where('item', 'like', '%' . $this->search . '%')
                ->orderBy('created_at', 'desc')
                ->paginate(10), // တစ်မျက်နှာလျှင် ၁၀ ခုပြမည်
        ];
    }

    public function save()
    {
        $this->validate([
            'item' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'item')->ignore($this->itemId),
            ],
            'unit' => 'required|string|max:50',
            'min_stock_level' => 'nullable|integer|min:0',
        ]);

        Item::updateOrCreate(
            ['id' => $this->itemId],
            [
                'item' => $this->item,
                'unit' => $this->unit,
                'min_stock_level' => $this->min_stock_level
            ]
        );

        session()->flash('message', $this->isEditMode ? 'Medicine Item Updated Successfully.' : 'Medicine Item Created Successfully.');

        $this->resetForm();
    }

    public function edit($id)
    {
        $itm = Item::findOrFail($id);
        $this->itemId = $itm->id;
        $this->item = $itm->item;
        $this->unit = $itm->unit;
        $this->min_stock_level = $itm->min_stock_level;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        try {
            Item::findOrFail($id)->delete();
            session()->flash('message', 'Medicine Item Deleted Successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'Cannot delete this item because it is being used in transactions.');
        }
    }

    public function resetForm()
    {
        $this->reset(['itemId', 'item', 'unit', 'min_stock_level', 'isEditMode']);
        $this->resetValidation();
    }

    // Search ရိုက်တဲ့အခါ Page 1 ကိုပြန်သွားရန်
    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            {{ $isEditMode ? 'Edit Medicine' : 'Add New Medicine' }}
        </h3>

        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Item Name</label>
                <input type="text" wire:model="item" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. Paracetamol 500mg">
                @error('item') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Unit of Measurement</label>
                <input type="text" wire:model="unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. Tablets, Vials, Boxes">
                @error('unit') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Minimum Stock Level</label>
                <input type="number" wire:model="min_stock_level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Alert Level e.g. 5000">
                <span class="text-xs text-gray-500 mt-1 block">Leave empty if not applicable.</span>
                @error('min_stock_level') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg w-full transition text-sm">
                    {{ $isEditMode ? 'Update Item' : 'Save Item' }}
                </button>
                @if($isEditMode)
                    <button type="button" wire:click="resetForm" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition text-sm">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
            <h3 class="text-lg font-bold text-gray-800">Medicine Directory</h3>

            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm pl-10 py-1.5" placeholder="Search items...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Min Level</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $itm)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $itm->item }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $itm->unit }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($itm->min_stock_level)
                                    <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-0.5 rounded">{{ number_format($itm->min_stock_level) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $itm->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="delete({{ $itm->id }})" wire:confirm="Are you sure you want to delete this item?" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No items found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 border-t border-gray-200 bg-white">
            {{ $items->links() }}
        </div>
    </div>
</div>
