<?php

namespace App\Enums;

/**
 * User roles for ReliefGrid.
 *
 * Roles are stored as strings on the users table and used by Policies and the
 * `role` middleware for role-based access control.
 */
enum Role: string
{
    case Admin = 'admin';
    case Coordinator = 'coordinator';
    case FieldOfficer = 'field_officer';
    case WarehouseOfficer = 'warehouse_officer';
    case ShelterAssessor = 'shelter_assessor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Coordinator => 'Coordinator',
            self::FieldOfficer => 'Field Officer',
            self::WarehouseOfficer => 'Warehouse Officer',
            self::ShelterAssessor => 'Shelter Assessor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access, manages user accounts and reference data.',
            self::Coordinator => 'Approves assistance cycles, stock transfers and shelter repairs; issues hazard alerts; verifies incident closure.',
            self::FieldOfficer => 'Registers households, issues tokens, submits repair requests, reports incidents and updates evacuation occupancy.',
            self::WarehouseOfficer => 'Receives and issues stock, requests transfers and issues repair materials.',
            self::ShelterAssessor => 'Assesses repair requests, uploads geo-tagged verification photos and completes repairs.',
        };
    }

    /** @return array<string,string> value => label */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
