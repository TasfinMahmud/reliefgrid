<?php

namespace App\Services;

use App\Enums\HouseholdStatus;
use App\Enums\TokenStatus;
use App\Models\Household;
use App\Models\HouseholdEvent;
use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Feature 1 – Household registration and token issuance.
 *
 * All state changes go through this service so that every action is recorded
 * in the household's event history and token invariants are enforced
 * (at most one active token per household, old tokens preserved).
 */
class HouseholdService
{
    /**
     * Register a new household, capture consent and (optionally) issue its first token.
     *
     * @param  array<string,mixed>  $data
     */
    public function register(array $data, User $actor, bool $issueToken = true): Household
    {
        return DB::transaction(function () use ($data, $actor, $issueToken) {
            $household = new Household($data);
            $household->code = Household::nextCode();
            $household->status = HouseholdStatus::Active;
            $household->registered_by = $actor->id;

            if (! empty($data['consent_given'])) {
                $household->consent_given = true;
                $household->consent_given_at = now();
                $household->consent_version = Household::CONSENT_VERSION;
            }
            $household->save();

            $this->log($household, $actor, 'registered', "Household registered in block {$household->block->code}.");
            if ($household->consent_given) {
                $this->log($household, $actor, 'consent_recorded', 'Consent recorded (version '.$household->consent_version.').');
            }

            if ($issueToken) {
                $this->issueToken($household, $actor);
            }

            return $household;
        });
    }

    /**
     * Update household data. Changed attributes are stored in the event log.
     *
     * @param  array<string,mixed>  $data
     */
    public function update(Household $household, array $data, User $actor): Household
    {
        return DB::transaction(function () use ($household, $data, $actor) {
            $consentNow = ! empty($data['consent_given']);
            unset($data['consent_given']);

            $household->fill($data);
            $changes = $household->getDirty();

            if ($consentNow && ! $household->consent_given) {
                $household->consent_given = true;
                $household->consent_given_at = now();
                $household->consent_version = Household::CONSENT_VERSION;
                $this->log($household, $actor, 'consent_recorded', 'Consent recorded (version '.Household::CONSENT_VERSION.').');
            }

            $household->save();

            if ($changes) {
                $this->log($household, $actor, 'updated', 'Household details updated: '.implode(', ', array_keys($changes)).'.', $changes);
            }

            return $household;
        });
    }

    public function deactivate(Household $household, string $reason, User $actor): Household
    {
        if (! $household->isActive()) {
            throw ValidationException::withMessages(['status' => 'This household is already inactive.']);
        }

        return DB::transaction(function () use ($household, $reason, $actor) {
            $household->status = HouseholdStatus::Inactive;
            $household->deactivated_at = now();
            $household->deactivated_by = $actor->id;
            $household->deactivation_reason = $reason;
            $household->save();

            // An inactive household must not hold a usable token.
            $active = $household->activeToken;
            if ($active) {
                $this->invalidateToken($active, TokenStatus::Revoked, 'Household deactivated: '.$reason, $actor);
                $this->log($household, $actor, 'token_revoked', "Token {$active->shortUid()} revoked because the household was deactivated.");
            }

            $this->log($household, $actor, 'deactivated', 'Household deactivated: '.$reason);

            return $household;
        });
    }

    public function reactivate(Household $household, User $actor): Household
    {
        if ($household->isActive()) {
            throw ValidationException::withMessages(['status' => 'This household is already active.']);
        }

        return DB::transaction(function () use ($household, $actor) {
            $household->status = HouseholdStatus::Active;
            $household->deactivated_at = null;
            $household->deactivated_by = null;
            $household->deactivation_reason = null;
            $household->save();

            $this->log($household, $actor, 'reactivated', 'Household reactivated. A new token must be issued.');

            return $household;
        });
    }

    /** Issue a brand-new token. Fails if the household already has an active one. */
    public function issueToken(Household $household, User $actor): Token
    {
        if (! $household->isActive()) {
            throw ValidationException::withMessages(['token' => 'Tokens can only be issued to active households.']);
        }
        if ($household->activeToken()->exists()) {
            throw ValidationException::withMessages(['token' => 'This household already has an active token. Use "reissue" instead.']);
        }

        return DB::transaction(function () use ($household, $actor) {
            $token = $household->tokens()->create([
                'token_uid' => Token::generateUid(),
                'status' => TokenStatus::Active,
                'issued_at' => now(),
                'issued_by' => $actor->id,
            ]);
            $this->log($household, $actor, 'token_issued', "Token {$token->shortUid()} issued.");

            return $token;
        });
    }

    /**
     * Reissue a token: the current active token is marked lost (or revoked) and
     * a replacement is issued. Both remain in the household's history.
     */
    public function reissueToken(Household $household, string $reason, User $actor, bool $lost = true): Token
    {
        if (! $household->isActive()) {
            throw ValidationException::withMessages(['token' => 'Tokens can only be reissued to active households.']);
        }

        return DB::transaction(function () use ($household, $reason, $actor, $lost) {
            $old = $household->activeToken;
            if ($old) {
                $this->invalidateToken($old, $lost ? TokenStatus::Lost : TokenStatus::Revoked, $reason, $actor);
            }

            $new = $household->tokens()->create([
                'token_uid' => Token::generateUid(),
                'status' => TokenStatus::Active,
                'issued_at' => now(),
                'issued_by' => $actor->id,
                'replaces_token_id' => $old?->id,
            ]);

            $this->log($household, $actor, 'token_reissued',
                $old
                    ? "Token {$old->shortUid()} marked ".($lost ? 'lost' : 'revoked')." ({$reason}); replacement {$new->shortUid()} issued."
                    : "Replacement token {$new->shortUid()} issued ({$reason}).");

            return $new;
        });
    }

    public function revokeToken(Token $token, string $reason, User $actor): Token
    {
        if (! $token->isActive()) {
            throw ValidationException::withMessages(['token' => 'Only active tokens can be revoked.']);
        }

        return DB::transaction(function () use ($token, $reason, $actor) {
            $this->invalidateToken($token, TokenStatus::Revoked, $reason, $actor);
            $this->log($token->household, $actor, 'token_revoked', "Token {$token->shortUid()} revoked: {$reason}");

            return $token;
        });
    }

    private function invalidateToken(Token $token, TokenStatus $status, string $reason, User $actor): void
    {
        $token->status = $status;
        $token->invalidated_at = now();
        $token->invalidated_by = $actor->id;
        $token->invalidation_reason = $reason;
        $token->save();
    }

    /** @param  array<string,mixed>|null  $changes */
    private function log(Household $household, ?User $actor, string $type, string $description, ?array $changes = null): HouseholdEvent
    {
        return $household->events()->create([
            'user_id' => $actor?->id,
            'event_type' => $type,
            'description' => $description,
            'changes' => $changes,
        ]);
    }
}
