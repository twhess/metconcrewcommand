<?php

namespace App\Policies;

use App\Models\MaintenanceSchedule;
use App\Models\User;

class MaintenanceSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function view(User $user, MaintenanceSchedule $schedule): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function update(User $user, MaintenanceSchedule $schedule): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function delete(User $user, MaintenanceSchedule $schedule): bool
    {
        return $user->hasPermission('equipment.delete');
    }
}
