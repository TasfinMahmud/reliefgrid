<?php

namespace App\Policies;

use App\Models\EvacuationSite;
use App\Models\User;

class EvacuationSitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EvacuationSite $site): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, EvacuationSite $site): bool
    {
        return $user->isManager();
    }

    public function updateOccupancy(User $user, EvacuationSite $site): bool
    {
        return $user->isFieldOfficer() || $user->isManager();
    }
}
