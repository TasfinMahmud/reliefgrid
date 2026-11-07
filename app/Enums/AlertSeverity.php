<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case Advisory = 'advisory';
    case Watch = 'watch';
    case Warning = 'warning';
    case Emergency = 'emergency';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Advisory => 'bg-info text-dark',
            self::Watch => 'bg-primary',
            self::Warning => 'bg-warning text-dark',
            self::Emergency => 'bg-danger',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Advisory => 1,
            self::Watch => 2,
            self::Warning => 3,
            self::Emergency => 4,
        };
    }
}
