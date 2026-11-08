<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * Feature 5 permissions.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isManager();
    }

    /** Receive stock / issue stock from this warehouse. */
    public function operate(User $user, Warehouse $warehouse): bool
    {
        return ($user->isWarehouseOfficer() || $user->isAdmin()) && $warehouse->is_active;
    }

    public function requestTransfer(User $user, Warehouse $warehouse): bool
    {
        return $this->operate($user, $warehouse);
    }
}
