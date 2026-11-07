<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

/**
 * Feature 1 permissions.
 * Field officers run the registration desk; coordinators and admins can do
 * everything a field officer can plus revoke tokens outright.
 */
class HouseholdPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Household $household): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isFieldOfficer() || $user->isManager();
    }

    public function update(User $user, Household $household): bool
    {
        return $user->isFieldOfficer() || $user->isManager();
    }

    public function deactivate(User $user, Household $household): bool
    {
        return ($user->isFieldOfficer() || $user->isManager()) && $household->isActive();
    }

    public function reactivate(User $user, Household $household): bool
    {
        return $user->isManager() && ! $household->isActive();
    }

    public function issueToken(User $user, Household $household): bool
    {
        return ($user->isFieldOfficer() || $user->isManager()) && $household->isActive();
    }

    public function reissueToken(User $user, Household $household): bool
    {
        return ($user->isFieldOfficer() || $user->isManager()) && $household->isActive();
    }

    public function revokeToken(User $user, Household $household): bool
    {
        return $user->isManager();
    }
}
