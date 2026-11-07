<?php

namespace App\Policies;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Incident $incident): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isFieldOfficer() || $user->isManager() || $user->isShelterAssessor();
    }

    public function investigate(User $user, Incident $incident): bool
    {
        return ($user->isFieldOfficer() || $user->isManager()) && $incident->status === IncidentStatus::Reported;
    }

    public function respond(User $user, Incident $incident): bool
    {
        return ($user->isFieldOfficer() || $user->isManager()) && $incident->status === IncidentStatus::UnderInvestigation;
    }

    public function addNote(User $user, Incident $incident): bool
    {
        return ! $incident->isClosed();
    }

    /** Verified closure is a management decision. */
    public function close(User $user, Incident $incident): bool
    {
        return $user->isManager() && $incident->status === IncidentStatus::ResponseInProgress;
    }
}
