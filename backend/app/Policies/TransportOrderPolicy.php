<?php

namespace App\Policies;

use App\Models\TransportOrder;
use App\Models\User;

class TransportOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('transport.view');
    }

    public function view(User $user, TransportOrder $order): bool
    {
        return $user->hasPermission('transport.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('transport.create');
    }

    public function assign(User $user, TransportOrder $order): bool
    {
        return $user->hasPermission('transport.assign');
    }

    public function execute(User $user, TransportOrder $order): bool
    {
        return $user->hasPermission('transport.execute');
    }

    public function cancel(User $user, TransportOrder $order): bool
    {
        return $user->hasPermission('transport.cancel');
    }
}
