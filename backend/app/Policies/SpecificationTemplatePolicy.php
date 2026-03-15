<?php

namespace App\Policies;

use App\Models\SpecificationTemplate;
use App\Models\User;

class SpecificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, SpecificationTemplate $template): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.create');
    }

    public function update(User $user, SpecificationTemplate $template): bool
    {
        return $user->hasPermission('projects.update');
    }

    public function delete(User $user, SpecificationTemplate $template): bool
    {
        return $user->hasPermission('projects.delete');
    }
}
