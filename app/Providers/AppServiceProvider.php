<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin Gate
        Gate::define('admin', function (User $user) {
            return $user->role === 'admin';
        });

        // Both of Admin and User Gate
        Gate::define('data-entry', function (User $user){
            return in_array($user->role, ['admin', 'data-entry']);
        });
    }
}
