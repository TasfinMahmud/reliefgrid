<?php

namespace App\Policies;

use App\Models\AssistanceCycle;
use App\Models\User;

/**
 * Feature 2 permissions. Rules can only be edited while the cycle is a draft;
 * activation is the coordinator's approval step that locks them.
 */
class AssistanceCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssistanceCycle $cycle): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, AssistanceCycle $cycle): bool
    {
        return $user->isManager() && $cycle->isDraft();
    }

    public function manageRules(User $user, AssistanceCycle $cycle): bool
    {
        return $user->isManager() && $cycle->isDraft();
    }

    public function activate(User $user, AssistanceCycle $cycle): bool
    {
        return $user->isManager() && $cycle->isDraft();
    }

    public function close(User $user, AssistanceCycle $cycle): bool
    {
        return $user->isManager() && $cycle->isActive();
    }

    public function delete(User $user, AssistanceCycle $cycle): bool
    {
        return $user->isManager() && $cycle->isDraft();
    }
}
