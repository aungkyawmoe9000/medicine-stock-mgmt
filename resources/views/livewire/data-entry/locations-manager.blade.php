<?php

use Livewire\Volt\Component;
use App\Models\Location;
use Illuminate\Validation\Rule;

new class extends Component {
    public $locations;
    public $locationId = null;
    public string $location = '';
    public string $office = '';
    public bool $isEditMode = false;

    public function mount()
    {
        $this->loadLocations();
    }

    public function loadLocations()
    {
        $this->locations = Location::orderBy('created_at', 'desc')->get();
    }

    public function save()
    {
        $this->validate([
            'location' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'location')->ignore($this->locationId),
            ],
            'office' => 'required|string|max:255',
        ]);

        Location::updateOrCreate(
            ['id' => $this->locationId],
            [
                'location' => $this->location,
                'office' => $this->office
            ]
        );

        session()->flash('message', $this->isEditMode ? 'Location Updated Successfully.' : 'Location Created Successfully.');

        $this->resetForm();
        $this->loadLocations();
    }

    public function edit($id)
    {
        $loc = Location::findOrFail($id);
        $this->locationId = $loc->id;
        $this->location = $loc->location;
        $this->office = $loc->office;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        try {
            Location::findOrFail($id)->delete();
            session()->flash('message', 'Location Deleted Successfully.');
            $this->loadLocations();
        } catch (\Illuminate\Database\QueryException $e) {
            // restrictOnDelete() သုံးထားသဖြင့် Transaction ရှိနေပါက ဖျက်မရရန် တားမြစ်ခြင်း
            session()->flash('error', 'Cannot delete this location because it is being used in transactions.');
        }
    }

    public function resetForm()
    {
        $this->reset(['locationId', 'location', 'office', 'isEditMode']);
        $this->resetValidation();
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            {{ $isEditMode ? 'Edit Location' : 'Add New Location' }}
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
                <label class="block text-sm font-medium text-gray-700">Location Name</label>
                <input type="text" wire:model="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. Clinic A (North)">
                @error('location') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Office / Branch</label>
                <input type="text" wire:model="office" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. Branch 1">
                @error('office') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg w-full transition text-sm">
                    {{ $isEditMode ? 'Update Location' : 'Save Location' }}
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
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Location List</h3>
            <span class="text-xs font-semibold bg-blue-100 text-blue-800 py-1 px-3 rounded-full">Total: {{ $locations->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Location Name</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Office</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($locations as $index => $loc)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loc->location }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loc->office }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $loc->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="delete({{ $loc->id }})" wire:confirm="Are you sure you want to delete this location?" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No locations found. Create one to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
