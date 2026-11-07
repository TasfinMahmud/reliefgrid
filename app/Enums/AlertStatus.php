<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Cancelled => 'bg-secondary',
            self::Expired => 'bg-dark',
        };
    }
}
