<?php

namespace App\Policies;

use App\Enums\RepairStatus;
use App\Models\ShelterRepairRequest;
use App\Models\User;

/**
 * Feature 12 – role-based permissions, one method per workflow step.
 * (Role helper methods come from the shared User model.)
 */
class ShelterRepairRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ShelterRepairRequest $request): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isFieldOfficer() || $user->isShelterAssessor() || $user->isManager() || $user->isAdmin();
    }

    public function assess(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isShelterAssessor() || $user->isAdmin())
            && $request->status === RepairStatus::Submitted;
    }

    public function approve(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isManager() || $user->isAdmin()) && $request->status === RepairStatus::Assessed;
    }

    public function reject(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isManager() || $user->isAdmin())
            && in_array($request->status, [RepairStatus::Submitted, RepairStatus::Assessed], true);
    }

    public function issueMaterials(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isWarehouseOfficer() || $user->isAdmin())
            && in_array($request->status, [RepairStatus::Approved, RepairStatus::MaterialsIssued], true);
    }

    public function addPhoto(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isShelterAssessor() || $user->isFieldOfficer() || $user->isManager() || $user->isAdmin())
            && ! $request->status->isTerminal();
    }

    public function complete(User $user, ShelterRepairRequest $request): bool
    {
        return ($user->isShelterAssessor() || $user->isManager() || $user->isAdmin())
            && in_array($request->status, [RepairStatus::Approved, RepairStatus::MaterialsIssued], true);
    }
}
