<?php

namespace App\Enums;

enum TokenStatus: string
{
    case Active = 'active';
    case Lost = 'lost';
    case Revoked = 'revoked';
    case Replaced = 'replaced';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Lost => 'bg-warning text-dark',
            self::Revoked => 'bg-danger',
            self::Replaced => 'bg-secondary',
        };
    }
}
