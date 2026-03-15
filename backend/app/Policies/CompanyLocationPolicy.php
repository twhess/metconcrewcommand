<?php

namespace App\Policies;

use App\Models\CompanyLocation;
use App\Models\User;

class CompanyLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('companies.view');
    }

    public function view(User $user, CompanyLocation $location): bool
    {
        return $user->hasPermission('companies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('companies.create');
    }

    public function update(User $user, CompanyLocation $location): bool
    {
        return $user->hasPermission('companies.update');
    }

    public function delete(User $user, CompanyLocation $location): bool
    {
        return $user->hasPermission('companies.delete');
    }
}
