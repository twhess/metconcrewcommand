<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view');
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.create');
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.update');
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.delete');
    }

    public function move(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.move');
    }

    public function adjust(User $user): bool
    {
        return $user->hasPermission('inventory.adjust');
    }
}
