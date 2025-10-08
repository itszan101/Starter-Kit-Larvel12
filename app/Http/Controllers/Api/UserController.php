<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // List semua user (admin only)
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection(User::all());
    }

    // Create user (admin only)
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'password'   => 'required|string|min:6|confirmed',
            'role'       => 'nullable|in:admin,user'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // Tetapkan role (default user jika kosong)
        $user->assignRole($validated['role'] ?? 'user');

        return new UserResource($user);
    }

    // Lihat detail user
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    // Update user (admin atau owner)
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'sometimes|required|email|unique:users,email,' . $user->id,
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'password'   => 'nullable|string|min:6|confirmed',
            'role'       => 'nullable|in:admin,user',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Hanya admin boleh ubah role
        if ($request->user()->hasRole('admin') && !empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    // Hapus user
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}