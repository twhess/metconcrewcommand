<?php

namespace App\Policies;

use App\Models\MileageLog;
use App\Models\User;

class MileageLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function view(User $user, MileageLog $log): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vehicles.update');
    }

    public function update(User $user, MileageLog $log): bool
    {
        return $user->hasPermission('vehicles.update');
    }

    public function delete(User $user, MileageLog $log): bool
    {
        return $user->hasPermission('vehicles.delete');
    }

    public function report(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }
}
