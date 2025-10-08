<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $userRole  = Role::create(['name' => 'user']);

        $admin = User::create([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email' => 'admin@test.com',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'password' => bcrypt('12344321'),
        ]);

        $admin->assignRole($adminRole);
    }
}