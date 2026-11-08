<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isWarehouseOfficer() || $user->isAdmin();
    }

    /** Coordinators (and admins) approve transfers; never the requester. */
    public function review(User $user, StockTransfer $transfer): bool
    {
        return $user->isManager() && $transfer->isPending()
            && ($transfer->requested_by !== $user->id || $user->isAdmin());
    }

    public function cancel(User $user, StockTransfer $transfer): bool
    {
        return $transfer->isPending() && ($transfer->requested_by === $user->id || $user->isAdmin());
    }
}
