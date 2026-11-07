<?php

namespace App\Policies;

use App\Enums\AlertStatus;
use App\Models\HazardAlert;
use App\Models\User;

class HazardAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HazardAlert $alert): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function cancel(User $user, HazardAlert $alert): bool
    {
        return $user->isManager() && $alert->status === AlertStatus::Active;
    }
}
