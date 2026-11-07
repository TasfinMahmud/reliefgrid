<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Enums\IncidentStatus;
use App\Models\EvacuationSite;
use App\Models\HazardAlert;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Feature 14 – Preparedness and incident response.
 *
 * Hazard alerts (block-level), evacuation site capacity/occupancy, and the
 * incident lifecycle through investigation and response to a verified closure.
 */
class PreparednessService
{
    // ---------------------------------------------------------------
    // Hazard alerts
    // ---------------------------------------------------------------

    /** @param array<string,mixed> $data */
    public function issueAlert(array $data, User $actor): HazardAlert
    {
        if (! empty($data['expires_at']) && now()->gte($data['expires_at'])) {
            throw ValidationException::withMessages(['expires_at' => 'Expiry must be in the future.']);
        }

        return DB::transaction(function () use ($data, $actor) {
            $alert = HazardAlert::create([
                'hazard_type' => $data['hazard_type'],
                'severity' => $data['severity'],
                'title' => $data['title'],
                'message' => $data['message'],
                'instructions' => $data['instructions'] ?? null,
                'status' => AlertStatus::Active,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
            ]);
            $alert->blocks()->sync($data['block_ids']);

            return $alert;
        });
    }

    public function cancelAlert(HazardAlert $alert, User $actor, string $reason): HazardAlert
    {
        if ($alert->status !== AlertStatus::Active) {
            throw ValidationException::withMessages(['status' => 'Only active alerts can be cancelled.']);
        }
        $alert->fill([
            'status' => AlertStatus::Cancelled,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ])->save();

        return $alert;
    }

    /** Mark alerts whose expiry has passed as expired (idempotent housekeeping). */
    public function expireAlerts(): int
    {
        return HazardAlert::where('status', AlertStatus::Active->value)
            ->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->update(['status' => AlertStatus::Expired->value]);
    }

    // ---------------------------------------------------------------
    // Evacuation sites
    // ---------------------------------------------------------------

    public function updateOccupancy(EvacuationSite $site, int $occupancy, User $actor, ?string $note = null): EvacuationSite
    {
        if (! $site->is_open) {
            throw ValidationException::withMessages(['occupancy' => 'This site is closed; reopen it before recording occupancy.']);
        }
        if ($occupancy < 0) {
            throw ValidationException::withMessages(['occupancy' => 'Occupancy cannot be negative.']);
        }
        if ($occupancy > $site->capacity) {
            throw ValidationException::withMessages(['occupancy' => "Occupancy cannot exceed the site capacity of {$site->capacity}."]);
        }

        return DB::transaction(function () use ($site, $occupancy, $actor, $note) {
            $site->occupancyLogs()->create([
                'previous_occupancy' => $site->current_occupancy,
                'occupancy' => $occupancy,
                'recorded_by' => $actor->id,
                'note' => $note,
            ]);
            $site->current_occupancy = $occupancy;
            $site->save();

            return $site;
        });
    }

    public function setSiteOpen(EvacuationSite $site, bool $open, User $actor): EvacuationSite
    {
        $site->is_open = $open;
        $site->save();
        $site->occupancyLogs()->create([
            'previous_occupancy' => $site->current_occupancy,
            'occupancy' => $site->current_occupancy,
            'recorded_by' => $actor->id,
            'note' => $open ? 'Site opened' : 'Site closed',
        ]);

        return $site;
    }

    // ---------------------------------------------------------------
    // Incidents
    // ---------------------------------------------------------------

    /** @param array<string,mixed> $data */
    public function reportIncident(array $data, User $actor): Incident
    {
        return DB::transaction(function () use ($data, $actor) {
            $incident = Incident::create(array_merge($data, [
                'code' => Incident::nextCode(),
                'status' => IncidentStatus::Reported,
                'reported_by' => $actor->id,
            ]));
            $this->logUpdate($incident, $actor, null, IncidentStatus::Reported, 'Incident reported.');

            return $incident;
        });
    }

    public function startInvestigation(Incident $incident, User $actor, string $notes): Incident
    {
        $this->assertStatus($incident, IncidentStatus::Reported);

        return DB::transaction(function () use ($incident, $actor, $notes) {
            $incident->fill([
                'investigated_by' => $actor->id,
                'investigation_started_at' => now(),
                'investigation_notes' => $notes,
            ]);

            return $this->transition($incident, IncidentStatus::UnderInvestigation, $actor, $notes);
        });
    }

    /** @param array{response_notes:string, households_affected?:int|null, people_affected?:int|null, injuries?:int|null, deaths?:int|null, evacuation_site_id?:int|null} $data */
    public function startResponse(Incident $incident, User $actor, array $data): Incident
    {
        $this->assertStatus($incident, IncidentStatus::UnderInvestigation);

        return DB::transaction(function () use ($incident, $actor, $data) {
            $incident->fill([
                'response_lead_id' => $actor->id,
                'response_started_at' => now(),
                'response_notes' => $data['response_notes'],
                'households_affected' => $data['households_affected'] ?? $incident->households_affected,
                'people_affected' => $data['people_affected'] ?? $incident->people_affected,
                'injuries' => $data['injuries'] ?? $incident->injuries,
                'deaths' => $data['deaths'] ?? $incident->deaths,
                'evacuation_site_id' => $data['evacuation_site_id'] ?? $incident->evacuation_site_id,
            ]);

            return $this->transition($incident, IncidentStatus::ResponseInProgress, $actor, $data['response_notes']);
        });
    }

    public function addNote(Incident $incident, User $actor, string $note): Incident
    {
        if ($incident->isClosed()) {
            throw ValidationException::withMessages(['note' => 'This incident is closed.']);
        }
        $this->logUpdate($incident, $actor, null, null, $note);

        return $incident;
    }

    /**
     * Verified closure: a coordinator/admin confirms the response was carried
     * out. Requires investigation and response notes to exist.
     */
    public function verifyAndClose(Incident $incident, User $actor, string $verificationNotes): Incident
    {
        $this->assertStatus($incident, IncidentStatus::ResponseInProgress);
        if (! $actor->isManager()) {
            throw ValidationException::withMessages(['status' => 'Only a coordinator or administrator can verify and close an incident.']);
        }
        if (blank($incident->investigation_notes) || blank($incident->response_notes)) {
            throw ValidationException::withMessages(['status' => 'Investigation and response notes must be recorded before closure.']);
        }

        return DB::transaction(function () use ($incident, $actor, $verificationNotes) {
            $incident->fill([
                'verified_by' => $actor->id,
                'closed_at' => now(),
                'verification_notes' => $verificationNotes,
            ]);

            return $this->transition($incident, IncidentStatus::Closed, $actor, 'Response verified: '.$verificationNotes);
        });
    }

    private function assertStatus(Incident $incident, IncidentStatus $expected): void
    {
        if ($incident->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => sprintf('This action requires the incident to be "%s" (it is "%s").', $expected->label(), $incident->status->label()),
            ]);
        }
    }

    private function transition(Incident $incident, IncidentStatus $to, User $actor, ?string $note): Incident
    {
        $from = $incident->status;
        $incident->status = $to;
        $incident->save();
        $this->logUpdate($incident, $actor, $from, $to, $note);

        return $incident;
    }

    private function logUpdate(Incident $incident, User $actor, ?IncidentStatus $from, ?IncidentStatus $to, ?string $note): void
    {
        $incident->updates()->create([
            'user_id' => $actor->id,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'note' => $note,
        ]);
    }
}
