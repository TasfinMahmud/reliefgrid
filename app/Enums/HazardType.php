<?php

namespace App\Enums;

enum HazardType: string
{
    case Flood = 'flood';
    case Landslide = 'landslide';
    case Cyclone = 'cyclone';
    case Storm = 'storm';
    case Fire = 'fire';
    case Heatwave = 'heatwave';
    case DiseaseOutbreak = 'disease_outbreak';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DiseaseOutbreak => 'Disease outbreak',
            default => ucfirst($this->value),
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
