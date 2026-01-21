<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Menampilkan semua user
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }

    // Menampilkan detail user
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    // Menambahkan user baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:6|confirmed',
            'profile_picture' => 'nullable|string|max:255', // ⬅️ PATH ONLY
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $existingUser = User::withTrashed()
            ->where('email', $validated['email'])
            ->first();

        if ($existingUser && $existingUser->trashed()) {
            $existingUser->restore();
            $existingUser->update($validated);

            return response()->json([
                'message' => 'User direstore dan diperbarui',
                'data' => $existingUser->load('roles'),
            ]);
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil dibuat',
            'data' => $user->load('roles'),
        ]);
    }

    // Update data user (Super Admin bisa ubah password juga)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'gender' => 'nullable|in:male,female',
            'profile_picture' => 'nullable|string|max:255', // ⬅️ PATH ONLY
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => $user->load('roles'),
        ]);
    }

    // Hapus user
    public function destroy(Request $request, $id)
    {
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => '403, you can’t delete your own account'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.'
        ]);
    }

    // User ubah profil dan password sendiri
    // Backend API
    public function updateSelf(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'email'      => 'required|email|unique:users,email,' . $user->id,

            // PATH ONLY
            'profile_picture' => 'nullable|string|max:255',

            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ]);

        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Password lama tidak cocok'], 422);
            }

            $validated['password'] = Hash::make($validated['new_password']);
        }

        unset(
            $validated['current_password'],
            $validated['new_password'],
            $validated['new_password_confirmation']
        );

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->only([
                'id',
                'first_name',
                'last_name',
                'email',
                'profile_picture'
            ]),
        ]);
    }
}
