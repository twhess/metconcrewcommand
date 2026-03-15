<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Yard;

class YardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function view(User $user, Yard $yard): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('equipment.create');
    }

    public function update(User $user, Yard $yard): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function delete(User $user, Yard $yard): bool
    {
        return $user->hasPermission('equipment.delete');
    }
}
