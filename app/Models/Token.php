<?php

namespace App\Models;

use App\Enums\TokenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Feature 1 – Opaque QR token issued to a household.
 *
 * The token UID carries no personal data; it is a random identifier that is
 * only meaningful when looked up in ReliefGrid. Every token ever issued is
 * kept so the full history of a household's cards is preserved.
 */
class Token extends Model
{
    use HasFactory;

    protected $table = 'household_tokens';

    protected $fillable = [
        'household_id', 'token_uid', 'status', 'issued_at', 'issued_by',
        'invalidated_at', 'invalidated_by', 'invalidation_reason', 'replaces_token_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TokenStatus::class,
            'issued_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function invalidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }

    /** The older token this one replaced (if it was a reissue). */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'replaces_token_id');
    }

    /** The newer token that replaced this one (if any). */
    public function replacedBy(): HasOne
    {
        return $this->hasOne(Token::class, 'replaces_token_id');
    }

    public function isActive(): bool
    {
        return $this->status === TokenStatus::Active;
    }

    /** Generate a new opaque, unguessable token UID (uppercase, QR friendly). */
    public static function generateUid(): string
    {
        do {
            $uid = 'RG-'.strtoupper(Str::random(20));
        } while (static::where('token_uid', $uid)->exists());

        return $uid;
    }

    /** Short, human-readable tail of the UID for display on cards. */
    public function shortUid(): string
    {
        return substr($this->token_uid, -6);
    }
}
