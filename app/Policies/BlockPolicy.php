<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Block $block): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Block $block): bool
    {
        return $user->isManager();
    }

    public function delete(User $user, Block $block): bool
    {
        return $user->isAdmin();
    }
}
