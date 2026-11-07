<?php

namespace App\Enums;

enum CycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary',
            self::Active => 'bg-success',
            self::Closed => 'bg-dark',
        };
    }
}
