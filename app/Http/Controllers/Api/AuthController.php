<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => [
                'required',
                'email',
                Rule::unique('users')->whereNull('deleted_at'), // pastikan hanya unique untuk user aktif
            ],
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female',
            'password'   => 'required|min:6',
        ]);

        // Cek apakah ada user soft deleted dengan email yang sama
        $existingUser = User::withTrashed()->where('email', $data['email'])->first();

        if ($existingUser && $existingUser->trashed()) {
            // Restore user lama
            $existingUser->restore();

            // Update data user lama dengan data baru
            $existingUser->update([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? $existingUser->birth_date,
                'gender'     => $data['gender'] ?? $existingUser->gender,
                'password'   => Hash::make($data['password']),
            ]);

            // Pastikan role user tetap ada
            if (!$existingUser->hasRole('user')) {
                $existingUser->assignRole('user');
            }

            return response()->json([
                'message' => 'Akun lama berhasil direstore dan diperbarui.',
                'data' => $existingUser->only(['id', 'first_name', 'last_name', 'email']),
            ], 200);
        }

        // Jika belum pernah ada user dengan email itu, buat baru
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('user'); // Default role user

        return response()->json([
            'message' => 'User berhasil didaftarkan.',
            'data' => $user->only(['id', 'first_name', 'last_name', 'email']),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Ambil data role dan permission dari Spatie Permission
        $roles = $user->getRoleNames(); // ex: ['super-admin']
        $permissions = $user->getAllPermissions()->pluck('name'); // ex: ['view users', 'edit users', ...]

        return response()->json([
            'token'       => $token,
            'roles'       => $roles,
            'permissions' => $permissions,
            'user'        => [
                'id'    => $user->id,
                'name'  => $user->last_name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
