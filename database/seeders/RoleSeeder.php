<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.create',
            'role.view',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.delete',
            'role.assignUser',
            'permission.assignRole',
        ];

        // Buat permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // Buat roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $user       = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);

        // Hubungkan role ke permissions
        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions([
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.create',
            'role.view',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.delete',
            'role.assignUser',
            'permission.assignRole',
        ]);
        $user->syncPermissions(['user.view']);
    }
}
