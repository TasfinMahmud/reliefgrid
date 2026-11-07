<?php

namespace App\Enums;

/**
 * Feature 12 – lifecycle of a shelter repair request.
 *
 *   submitted -> assessed -> approved -> materials_issued -> completed
 *        \           \
 *         `-----------`--> rejected (terminal)
 *
 * A request without any material needs may go straight from approved to completed.
 */
enum RepairStatus: string
{
    case Submitted = 'submitted';
    case Assessed = 'assessed';
    case Approved = 'approved';
    case MaterialsIssued = 'materials_issued';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Assessed => 'Assessed',
            self::Approved => 'Approved',
            self::MaterialsIssued => 'Materials issued',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'text-bg-secondary',
            self::Assessed => 'text-bg-info',
            self::Approved => 'text-bg-primary',
            self::MaterialsIssued => 'text-bg-warning',
            self::Completed => 'text-bg-success',
            self::Rejected => 'text-bg-danger',
        };
    }

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Submitted => [self::Assessed, self::Rejected],
            self::Assessed => [self::Approved, self::Rejected],
            self::Approved => [self::MaterialsIssued, self::Completed],
            self::MaterialsIssued => [self::Completed],
            self::Completed, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Rejected;
    }

    /** Statuses in which the request is still being worked on. */
    public static function open(): array
    {
        return [self::Submitted, self::Assessed, self::Approved, self::MaterialsIssued];
    }

    /** @return array<string,string> value => label, for filter dropdowns */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
