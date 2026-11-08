<?php

namespace App\Enums;

/**
 * Types of entries in the append-only stock movement ledger.
 * Inbound types carry a positive quantity, outbound types a negative one.
 */
enum MovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt',
            self::Issue => 'Issue',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::Receipt, self::TransferIn], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Receipt => 'bg-success',
            self::Issue => 'bg-danger',
            self::TransferOut => 'bg-warning text-dark',
            self::TransferIn => 'bg-info text-dark',
        };
    }
}
