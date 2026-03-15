<?php

namespace App\Policies;

use App\Models\MaintenanceRecord;
use App\Models\User;

class MaintenanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function view(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function update(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function delete(User $user, MaintenanceRecord $record): bool
    {
        return $user->hasPermission('equipment.delete');
    }
}
