<?php

namespace App\Enums;

/**
 * Incident lifecycle: reported -> under_investigation -> response_in_progress -> closed
 * Closure is only possible through a response verification by a coordinator/admin.
 */
enum IncidentStatus: string
{
    case Reported = 'reported';
    case UnderInvestigation = 'under_investigation';
    case ResponseInProgress = 'response_in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::UnderInvestigation => 'Under investigation',
            self::ResponseInProgress => 'Response in progress',
            self::Closed => 'Closed (verified)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Reported => 'bg-secondary',
            self::UnderInvestigation => 'bg-info text-dark',
            self::ResponseInProgress => 'bg-warning text-dark',
            self::Closed => 'bg-success',
        };
    }

    public function next(): ?IncidentStatus
    {
        return match ($this) {
            self::Reported => self::UnderInvestigation,
            self::UnderInvestigation => self::ResponseInProgress,
            self::ResponseInProgress => self::Closed,
            self::Closed => null,
        };
    }
}
