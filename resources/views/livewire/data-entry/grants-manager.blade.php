<?php

use Livewire\Volt\Component;
use App\Models\Grant;
use Illuminate\Validation\Rule;

new class extends Component {
    public $grants;
    public $grantId = null;
    public string $grantName = '';
    public bool $isEditMode = false;

    public function mount()
    {
        $this->loadGrants();
    }

    public function loadGrants()
    {
        $this->grants = Grant::orderBy('created_at', 'desc')->get();
    }

    public function save()
    {
        $this->validate([
            'grantName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grants', 'grant')->ignore($this->grantId),
            ],
        ]);

        Grant::updateOrCreate(
            ['id' => $this->grantId],
            ['grant' => $this->grantName]
        );

        session()->flash('message', $this->isEditMode ? 'Grant Updated Successfully.' : 'Grant Created Successfully.');

        $this->resetForm();
        $this->loadGrants();
    }

    public function edit($id)
    {
        $grantData = Grant::findOrFail($id);
        $this->grantId = $grantData->id;
        $this->grantName = $grantData->grant;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        try {
            Grant::findOrFail($id)->delete();
            session()->flash('message', 'Grant Deleted Successfully.');
            $this->loadGrants();
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'Cannot delete this grant because it is being used in transactions.');
        }
    }

    public function resetForm()
    {
        $this->reset(['grantId', 'grantName', 'isEditMode']);
        $this->resetValidation();
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            {{ $isEditMode ? 'Edit Grant' : 'Add New Grant' }}
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
                <label class="block text-sm font-medium text-gray-700">Grant Name / Donor</label>
                <input type="text" wire:model="grantName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. UNICEF-2026">
                @error('grantName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg w-full transition text-sm">
                    {{ $isEditMode ? 'Update Grant' : 'Save Grant' }}
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
            <h3 class="text-lg font-bold text-gray-800">Grant List</h3>
            <span class="text-xs font-semibold bg-blue-100 text-blue-800 py-1 px-3 rounded-full">Total: {{ $grants->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Grant Name</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($grants as $index => $grantData)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $grantData->grant }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $grantData->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="delete({{ $grantData->id }})" wire:confirm="Are you sure you want to delete this grant?" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                No grants found. Create one to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
