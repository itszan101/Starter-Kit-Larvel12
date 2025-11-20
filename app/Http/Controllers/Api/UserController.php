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
            'last_name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:6|confirmed',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        // Cek apakah ada user soft deleted dengan email yang sama
        $existingUser = User::withTrashed()->where('email', $validated['email'])->first();

        if ($existingUser && $existingUser->trashed()) {
            // Jika ditemukan user soft deleted, restore user itu
            $existingUser->restore();

            // Update data user lama dengan data baru
            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store('profiles', 'public');
                $validated['profile_picture'] = $path;
            }

            $validated['password'] = Hash::make($validated['password']);
            $existingUser->update($validated);

            return response()->json([
                'message' => 'User lama berhasil direstore dan diperbarui.',
                'data' => $existingUser->load('roles'),
            ]);
        }

        // Kalau tidak ada user lama, buat user baru seperti biasa
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $validated['profile_picture'] = $path;
        }

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json([
            'message' => 'User baru berhasil dibuat.',
            'data' => $user->load('roles'),
        ]);
    }

    // Update data user (Super Admin bisa ubah password juga)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Upload foto baru jika ada
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $validated['profile_picture'] = $path;
        }

        // Ubah password jika dikirim
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // jangan timpa password lama
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Data user berhasil diperbarui.',
            'data' => $user->load('roles'),
        ]);
    }

    // Hapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    // User ubah profil dan password sendiri
    public function updateSelf(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|max:2048',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        // Jika user ingin ubah password
        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Password lama tidak cocok.'], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $user->profile_picture = $path;
        }

        $user->first_name = $validated['first_name'] ?? $user->first_name;
        $user->last_name = $validated['last_name'] ?? $user->last_name;
        $user->birth_date = $validated['birth_date'] ?? $user->birth_date;
        $user->gender = $validated['gender'] ?? $user->gender;
        $user->email = $validated['email'] ?? $user->email;

        $user->save();

        return response()->json([
            'message' => 'Profil Anda berhasil diperbarui.',
            'data' => $user->only(['id', 'first_name', 'last_name', 'email', 'profile_picture']),
        ]);
    }
}
