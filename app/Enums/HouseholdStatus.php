<?php

namespace App\Enums;

enum HouseholdStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return $this === self::Active ? 'bg-success' : 'bg-secondary';
    }
}
