<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // Create a Admin user for testing purposes
        User::factory()->create([
            'name' => 'woisa-admin',
            'email' => 'admin@woisa.org',
            'password' => '4321W0isa', // Auto Hash by Bcrypt due to the casts from User Model
            'role' => 'admin',
        ]);

        // Create Data-Entry user
        User::factory()->create([
            'name' => 'woisa-data-entry',
            'email' => 'data-entry@woisa.org',
            'password' => '4321W0isa', // Auto Hash by Bcrypt due to the casts from User Model
            'role' => 'data-entry',
        ]);
    }
}
