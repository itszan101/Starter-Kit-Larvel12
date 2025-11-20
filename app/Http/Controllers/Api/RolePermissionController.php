<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    // List semua role
    public function getRoles()
    {
        return response()->json([
            'roles' => Role::all()
        ]);
    }

    // Buat role baru
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'guard_name' => 'nullable|string',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web'
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    // List semua permission
    public function getPermissions()
    {
        return response()->json([
            'permissions' => Permission::all()
        ]);
    }

    // Tambah permission baru
    public function addPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web',
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    // Menampilkan semua role beserta permission-nya
    public function getAllRolesWithPermissions()
    {
        $roles = Role::with('permissions')->get();

        $data = $roles->map(function ($role) {
            return [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return response()->json([
            'roles' => $data
        ]);
    }

    // Tambah & hapus permission dari role (assign + remove)
    public function updateRolePermissions(Request $request, $roleName)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Ambil role berdasarkan nama
        $role = Role::where('name', $roleName)->firstOrFail();

        // Ambil semua permission saat ini
        $currentPermissions = $role->permissions->pluck('name')->toArray();

        // Ambil permission baru dari request (bisa kosong)
        $newPermissions = $request->input('permissions', []);

        // Hitung permission yang harus ditambah dan dihapus
        $toAdd = array_diff($newPermissions, $currentPermissions);
        $toRemove = array_diff($currentPermissions, $newPermissions);

        // Tambahkan permission baru
        if (!empty($toAdd)) {
            $role->givePermissionTo($toAdd);
        }

        // Hapus permission yang tidak lagi dicentang
        if (!empty($toRemove)) {
            $role->revokePermissionTo($toRemove);
        }

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'added' => array_values($toAdd),
            'removed' => array_values($toRemove),
            'role' => $role->load('permissions')
        ]);
    }

    // Tambah & Hapus Role User (Assign + Remove)
    public function updateUserRoles(Request $request, $id)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        // Ambil user
        $user = User::findOrFail($id);

        // Ambil role lama dan role baru
        $currentRoles = $user->getRoleNames()->toArray();
        $newRoles = $request->input('roles', []);

        // Hitung perbedaan
        $toAdd = array_diff($newRoles, $currentRoles);
        $toRemove = array_diff($currentRoles, $newRoles);

        // Tambah role baru
        if (!empty($toAdd)) {
            $user->assignRole($toAdd);
        }

        // Hapus role yang tidak lagi ada
        if (!empty($toRemove)) {
            $user->removeRole($toRemove);
        }

        // (Opsional) bisa juga pakai syncRoles jika ingin langsung sinkron total:
        // $user->syncRoles($newRoles);

        return response()->json([
            'message' => 'User roles updated successfully.',
            'added' => array_values($toAdd),
            'removed' => array_values($toRemove),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    // Hapus Role
    public function deleteRole($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found'
            ], 404);
        }

        // Cek apakah role sedang digunakan oleh user
        $usersWithRole = User::role($role->name)->count();

        if ($usersWithRole > 0) {
            return response()->json([
                'message' => "Role '{$role->name}' tidak dapat dihapus karena sedang digunakan oleh {$usersWithRole} user."
            ], 409); // 409 Conflict
        }

        // Jika tidak digunakan, hapus role
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ], 200);
    }


    // Hapus Permission
    public function deletePermission($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission not found'
            ], 404);
        }

        // Cek apakah permission masih digunakan oleh role
        $rolesWithPermission = Role::whereHas('permissions', function ($q) use ($permission) {
            $q->where('id', $permission->id);
        })->count();

        if ($rolesWithPermission > 0) {
            return response()->json([
                'message' => "Permission '{$permission->name}' tidak dapat dihapus karena sedang digunakan oleh {$rolesWithPermission} role."
            ], 409); // 409 Conflict
        }

        // Hapus permission jika aman
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ], 200);
    }
}