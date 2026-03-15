<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacation;

class VacationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vacations.view');
    }

    public function view(User $user, Vacation $vacation): bool
    {
        return $user->hasPermission('vacations.view') || $vacation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vacations.create');
    }

    public function update(User $user, Vacation $vacation): bool
    {
        return $user->hasPermission('vacations.update') || $vacation->user_id === $user->id;
    }

    public function delete(User $user, Vacation $vacation): bool
    {
        return $user->hasPermission('vacations.delete') || $vacation->user_id === $user->id;
    }

    public function approve(User $user, Vacation $vacation): bool
    {
        return $user->hasPermission('vacations.approve');
    }
}
