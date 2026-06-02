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

// User Management route for Admin, accessible only to users with the 'admin' role
Route::view('/admin/users', 'admin.users')
    ->middleware(['auth', 'can:admin'])
    ->name('admin.users');

// middleware for Data Entry
Route::view('/data-entry/dashboard', 'data-entry.dashboard')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.dashboard');

// Data Entry routes for managing locations and items
Route::view('/data-entry/locations', 'data-entry.locations')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.locations');

// Data Entry routes for managing items
Route::view('/data-entry/items', 'data-entry.items')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.items');

// Route for managing grants, accessible only to users with the 'data-entry' role
Route::view('/data-entry/grants', 'data-entry.grants')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.grants');

// Route for managing projects, accessible only to users with the 'data-entry' role
Route::view('/data-entry/projects', 'data-entry.projects')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.projects');

// Route for managing stock-in, accessible only to users with the 'data-entry' role
Route::view('/data-entry/stock-in', 'data-entry.stock-in')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.stock-in');

// Route for managing stock-out, accessible only to users with the 'data-entry' role
Route::view('/data-entry/stock-out', 'data-entry.stock-out')
    ->middleware(['auth', 'can:data-entry'])
    ->name('data-entry.stock-out');

// Route for generating reports, accessible only to authenticated users
Route::view('/report', 'report')
    ->middleware(['auth'])
    ->name('report');

Route::view('/profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
