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

    // Assign permission ke role
    public function assignPermissionToRole(Request $request, $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::where('name', $role)->firstOrFail();
        $role->givePermissionTo($request->permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'role' => $role->load('permissions')
        ]);
    }

    // Hapus permission dari role
    public function removePermissionFromRole(Request $request, $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::where('name', $role)->firstOrFail();
        $role->revokePermissionTo($request->permissions);

        return response()->json([
            'message' => 'Permissions removed successfully',
            'role' => $role->load('permissions')
        ]);
    }

    // Beri role ke user
    public function assignRoleToUser(Request $request, $id)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user = User::findOrFail($id);
        $user->syncRoles($request->roles);

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    // Hapus role dari user
    public function removeRoleFromUser(Request $request, $id)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user = User::findOrFail($id);
        foreach ($request->roles as $role) {
            $user->removeRole($role);
        }

        return response()->json([
            'message' => 'Roles removed successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }
}
