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

        $role = Role::where('name', $roleName)->firstOrFail();

        // Cegah user ngedit permission dari role yang dia punya sendiri
        if ($request->user()->hasRole($roleName)) {
            return response()->json([
                'message' => '403, you can’t edit permission from your own role.',
            ], 403);
        }

        $currentPermissions = $role->permissions->pluck('name')->toArray();
        $newPermissions = $request->input('permissions', []);

        $toAdd = array_diff($newPermissions, $currentPermissions);
        $toRemove = array_diff($currentPermissions, $newPermissions);

        if (!empty($toAdd)) {
            $role->givePermissionTo($toAdd);
        }

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

        // Cegah user ngedit role dirinya sendiri
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => '403, you can’t edit your own role',
            ], 403);
        }

        $user = User::findOrFail($id);

        $currentRoles = $user->getRoleNames()->toArray();
        $newRoles = $request->input('roles', []);

        $toAdd = array_diff($newRoles, $currentRoles);
        $toRemove = array_diff($currentRoles, $newRoles);

        if (!empty($toAdd)) {
            $user->assignRole($toAdd);
        }

        if (!empty($toRemove)) {
            $user->removeRole($toRemove);
        }

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
