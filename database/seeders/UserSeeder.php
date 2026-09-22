<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed customer user accounts.
     */
    public function run(): void
    {
        $user1 = User::firstOrCreate(
            ['email' => 'user@badminton.test'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user1->assignRole('user');

        $user2 = User::firstOrCreate(
            ['email' => 'user2@badminton.test'],
            [
                'name' => 'Siti Rahma',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user2->assignRole('user');
    }
}

