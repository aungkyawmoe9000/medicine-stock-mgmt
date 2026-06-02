<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// အဆင့် (၁) က Layout ကို အသုံးပြုမည်ဟု ညွှန်ပြခြင်း
new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex-grow flex flex-col w-full">

    {{-- header area --}}
    <header class="bg-blue-900 text-white py-6 shadow-md">
        <div class="container mx-auto px-4 flex items-center justify-start gap-6">
            <div class="w-32 h-16 bg-blue-100 text-blue-900 flex items-center justify-center rounded font-bold shrink-0">
                Logo (2:1)
            </div>
            <h1 class="text-3xl font-bold uppercase tracking-wider">Medicine Balance Tracker</h1>
        </div>
    </header>

    {{-- main content area --}}
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md border border-blue-200">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">System Login</h2>

            <form wire:submit="login">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-semibold text-gray-600 mb-2">Username</label>
                    <input type="text" wire:model="form.username" id="username" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border" required placeholder="Enter your username">
                    @error('form.username') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold text-gray-600 mb-2">Password</label>
                    <input type="password" wire:model="form.password" id="password" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border" required placeholder="Enter your password">
                    @error('form.password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                    <span wire:loading.remove>Login</span>
                    <span wire:loading>Authenticating...</span>
                </button>
            </form>
        </div>
    </main>

    {{-- footer area --}}
    <footer class="bg-[#2a3c75] text-white py-5 mt-auto w-full text-center">
    <p class="text-sm font-medium tracking-wide">
        Developed by Aung Kyaw Moe
    </p>
    <p class="text-xs text-blue-200 mt-1.5 font-light tracking-wider">
        Full-Stack Dev | AI & ML | Cloud Infra | Workspace Admin
    </p>
</footer>

</div>
