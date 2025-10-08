<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Admin bisa melihat semua user
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole('admin');
    }

    // Admin bisa melihat siapa pun, user hanya dirinya sendiri
    public function view(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin') || $authUser->id === $user->id;
    }

    // Hanya admin bisa create user baru
    public function create(User $authUser): bool
    {
        return $authUser->hasRole('admin');
    }

    // Admin bisa update siapa pun, user hanya dirinya sendiri
    public function update(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin') || $authUser->id === $user->id;
    }

    // Hanya admin bisa delete user
    public function delete(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin');
    }
}