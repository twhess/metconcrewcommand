<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    /**
     * Check if a user has a specific permission
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * Check if a user has a specific role
     */
    public function userHasRole(User $user, string $roleName): bool
    {
        return $user->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if a user has any of the specified roles
     */
    public function userHasAnyRole(User $user, array $roleNames): bool
    {
        return $user->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if a user has all of the specified permissions
     */
    public function userHasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(User $user): array
    {
        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }
}
