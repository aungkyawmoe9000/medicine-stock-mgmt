<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// After successful login, this route will separate the routes based on the user role (admin or data-entry).
Route::get('/dashboard', function(){
    if (auth()->user()->role === 'admin'){
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('data-entry.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// middleware for Admin
Route::view('/admin/dashboard', 'admin.dashboard')
    ->middleware(['auth', 'can:admin'])
    ->name('admin.dashboard');

// middleware for Data Entry
Route::view('/data-entry/dashboard', 'data-entry.dashboard')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.dashboard');

Route::view('/profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
