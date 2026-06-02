<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

new class extends Component {
    public $users;
    public $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'data-entry';
    public bool $isEditMode = false;

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        // User တစ်ဦးချင်းစီ၏ Transaction အရေအတွက်နှင့် နောက်ဆုံး Transaction အချိန်ကိုပါ ဆွဲထုတ်မည်
        $this->users = User::withCount('transactions')
            ->withMax('transactions', 'created_at')
            ->orderBy('role', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($this->userId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'role' => 'required|in:admin,data-entry',
        ];

        if (!$this->isEditMode || !empty($this->password)) {
            $rules['password'] = 'required|string|min:6';
        }

        $this->validate($rules);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if (!empty($this->password)) {
            $userData['password'] = $this->password;
        }

        User::updateOrCreate(['id' => $this->userId], $userData);

        session()->flash('message', $this->isEditMode ? 'User Updated Successfully.' : 'User Created Successfully.');

        $this->resetForm();
        $this->loadUsers();
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account while logged in!');
            return;
        }

        try {
            User::findOrFail($id)->delete();
            session()->flash('message', 'User Deleted Successfully.');
            $this->loadUsers();
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'Cannot delete this user because they have recorded transactions in the system.');
        }
    }

    public function resetForm()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'isEditMode']);
        $this->resetValidation();
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            {{ $isEditMode ? 'Edit User Account' : 'Create New User' }}
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
                <label class="block text-sm font-medium text-gray-700">Username (Login ID) <span class="text-red-500">*</span></label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. data-entry-2">
                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. user@woisa.org">
                @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">System Role <span class="text-red-500">*</span></label>
                <select wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="data-entry">Data Entry (စာရင်းသွင်း/ကြည့်ရှုရန်)</option>
                    <option value="admin">Administrator (အလုံးစုံထိန်းချုပ်ခွင့်)</option>
                </select>
                @error('role') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Password {{ !$isEditMode ? '<span class="text-red-500">*</span>' : '' }}
                </label>
                <input type="text" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="{{ $isEditMode ? 'Leave blank to keep current password' : 'Enter a strong password' }}">
                @if($isEditMode)
                    <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change the password.</p>
                @endif
                @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg w-full transition text-sm">
                    {{ $isEditMode ? 'Update User' : 'Create User' }}
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
            <h3 class="text-lg font-bold text-gray-800">System Users</h3>
            <span class="text-xs font-semibold bg-blue-100 text-blue-800 py-1 px-3 rounded-full">Total: {{ $users->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Last Active</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <div>{{ $user->name }}</div>
                                <div class="text-xs text-gray-500 font-normal">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800">YOU</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($user->transactions_max_created_at)
                                    {{ \Carbon\Carbon::parse($user->transactions_max_created_at)->format('d M Y, h:i A') }}
                                @else
                                    <span class="text-gray-400 italic">No Activity Yet</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>

                                @if(auth()->id() !== $user->id)
                                    @if($user->transactions_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-50 text-red-400 cursor-not-allowed" title="Has transaction records">
                                            Can't Remove
                                        </span>
                                    @else
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="Are you sure you want to delete this user? This action cannot be undone." class="text-red-600 hover:text-red-900 font-bold">Delete</button>
                                    @endif
                                @else
                                    <span class="text-gray-300 cursor-not-allowed ml-1">Delete</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
