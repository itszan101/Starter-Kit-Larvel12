<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan observer agar tidak auto-assign role "user"
        \App\Models\User::withoutEvents(function () {
            // Super Admin
            $superAdmin = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'first_name' => 'Super',
                    'last_name'  => 'Admin',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'password' => Hash::make('12344321'),
                ]
            );
            $superAdmin->syncRoles(['super-admin']);

            // Admin
            $admin = User::firstOrCreate(
                ['email' => 'admin2@test.com'],
                [
                    'first_name' => 'Admin',
                    'last_name'  => 'App',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'password' => Hash::make('12344321'),
                ]
            );
            $admin->syncRoles(['admin']);

            // User biasa
            $user = User::firstOrCreate(
                ['email' => 'user@test.com'],
                [
                    'first_name' => 'User',
                    'last_name'  => 'Biasa',
                    'birth_date' => '1995-05-05',
                    'gender' => 'male',
                    'password' => Hash::make('12344321'),
                ]
            );
            $user->syncRoles(['user']);
        });
    }
}
