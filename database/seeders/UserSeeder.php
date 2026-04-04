<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@flightbookplatform.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        // Standard demo user
        User::firstOrCreate(
            ['email' => 'user@flightbookplatform.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ]
        );
    }
}
