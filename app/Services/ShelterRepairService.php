<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Models\Household;
use App\Models\ShelterRepairEvent;
use App\Models\ShelterRepairMaterial;
use App\Models\ShelterRepairRequest;
use App\Models\ShelterVerificationPhoto;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Feature 12 – business rules of the shelter repair workflow.
 *
 * Every transition goes through move(), which checks the RepairStatus state machine,
 * stamps the actor and writes a timeline event, all inside one transaction.
 */
class ShelterRepairService
{
    public function submit(array $data, User $actor): ShelterRepairRequest
    {
        return DB::transaction(function () use ($data, $actor) {
            $household = Household::query()->findOrFail($data['household_id']);
            if (method_exists($household, 'isActive') && ! $household->isActive()) {
                throw ValidationException::withMessages(['household_id' => 'Repairs can only be requested for active households.']);
            }

            $request = ShelterRepairRequest::create([
                'household_id' => $household->id,
                'block_id' => $household->block_id,
                'damage_type' => $data['damage_type'],
                'description' => $data['description'],
                'status' => RepairStatus::Submitted,
                'reported_by' => $actor->id,
            ]);
            $request->update(['code' => sprintf('SR-%05d', $request->id)]);

            $this->log($request, $actor, 'submitted', null, RepairStatus::Submitted, $data['description']);

            return $request;
        });
    }

    /**
     * Assessment: grade the damage and list the materials the repair needs.
     *
     * @param  array{severity:string, notes?:?string, estimated_days?:?int, materials?:array<int,array{item_name:string,unit:string,quantity_required:numeric}>}  $data
     */
    public function assess(ShelterRepairRequest $request, array $data, User $actor): ShelterRepairRequest
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $request = $this->lock($request);
            $this->guardTransition($request, RepairStatus::Assessed);

            $request->materials()->delete();
            foreach ($data['materials'] ?? [] as $line) {
                $request->materials()->create([
                    'item_name' => $line['item_name'],
                    'unit' => $line['unit'],
                    'quantity_required' => round((float) $line['quantity_required'], 2),
                ]);
            }

            $request->fill([
                'severity' => $data['severity'],
                'assessment_notes' => $data['notes'] ?? null,
                'estimated_days' => $data['estimated_days'] ?? null,
                'assessed_by' => $actor->id,
                'assessed_at' => now(),
            ]);

            return $this->move($request, RepairStatus::Assessed, $actor, 'assessed', $data['notes'] ?? null);
        });
    }

    public function approve(ShelterRepairRequest $request, User $actor, ?string $note = null): ShelterRepairRequest
    {
        return DB::transaction(function () use ($request, $actor, $note) {
            $request = $this->lock($request);
            $this->guardTransition($request, RepairStatus::Approved);

            $request->fill(['approved_by' => $actor->id, 'approved_at' => now()]);

            return $this->move($request, RepairStatus::Approved, $actor, 'approved', $note);
        });
    }

    public function reject(ShelterRepairRequest $request, string $reason, User $actor): ShelterRepairRequest
    {
        return DB::transaction(function () use ($request, $reason, $actor) {
            $request = $this->lock($request);
            $this->guardTransition($request, RepairStatus::Rejected);

            $request->fill([
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $this->move($request, RepairStatus::Rejected, $actor, 'rejected', $reason);
        });
    }

    /**
     * Issue (part of) a material line. When the stock module (Feature 5) is installed and a batch is
     * given, the issue is posted to the stock ledger; otherwise it is recorded on the repair only.
     * Once every material line is fully issued the request moves to "materials issued".
     */
    public function issueMaterial(ShelterRepairMaterial $material, float $quantity, User $actor, ?int $stockBatchId = null): ShelterRepairMaterial
    {
        return DB::transaction(function () use ($material, $quantity, $actor, $stockBatchId) {
            $request = $this->lock($material->repairRequest);

            if (! in_array($request->status, [RepairStatus::Approved, RepairStatus::MaterialsIssued], true)) {
                throw ValidationException::withMessages(['quantity' => 'Materials can only be issued for an approved repair.']);
            }

            $material->refresh();
            $quantity = round($quantity, 2);
            if ($quantity <= 0 || $quantity > $material->remaining()) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than 0 and not more than the remaining '.number_format($material->remaining(), 2).' '.$material->unit.'.',
                ]);
            }

            if ($stockBatchId !== null) {
                $this->postToLedger($stockBatchId, $quantity, $actor, $request, $material);
                $material->stock_batch_id = $stockBatchId;
            }

            $material->quantity_issued = round((float) $material->quantity_issued + $quantity, 2);
            $material->save();

            $this->log($request, $actor, 'material_issued', $request->status, $request->status,
                sprintf('%s %s of %s issued%s', number_format($quantity, 2), $material->unit, $material->item_name,
                    $stockBatchId ? " (stock batch #{$stockBatchId})" : ''));

            if ($request->status === RepairStatus::Approved && $request->allMaterialsIssued()) {
                $this->move($request, RepairStatus::MaterialsIssued, $actor, 'materials_issued', 'All materials issued.');
            }

            return $material;
        });
    }

    /**
     * Attach a verification photo. The photo must be geo-tagged: coordinates are read from the JPEG
     * EXIF GPS block, or - for photos taken in the browser - come from the device geolocation.
     */
    public function addPhoto(ShelterRepairRequest $request, UploadedFile $file, User $actor, array $device = [], ?string $caption = null): ShelterVerificationPhoto
    {
        if ($request->status->isTerminal()) {
            throw ValidationException::withMessages(['photo' => 'This request is closed; no more photos can be added.']);
        }

        $geo = $this->extractExifGps($file->getRealPath());
        $source = ShelterVerificationPhoto::SOURCE_EXIF;
        $accuracy = null;

        if ($geo === null) {
            $lat = $device['latitude'] ?? null;
            $lng = $device['longitude'] ?? null;
            if (($device['source'] ?? null) !== ShelterVerificationPhoto::SOURCE_DEVICE
                || ! $this->validCoordinates($lat, $lng)) {
                throw ValidationException::withMessages([
                    'photo' => 'The photograph is not geo-tagged. Upload a photo with GPS metadata, or capture the device location with the button below.',
                ]);
            }
            $geo = ['latitude' => (float) $lat, 'longitude' => (float) $lng, 'captured_at' => null];
            $source = ShelterVerificationPhoto::SOURCE_DEVICE;
            $accuracy = isset($device['accuracy']) && is_numeric($device['accuracy']) ? (float) $device['accuracy'] : null;
        }

        return DB::transaction(function () use ($request, $file, $actor, $geo, $source, $accuracy, $caption) {
            $path = $file->store('repair-photos/'.($request->code ?? $request->id), 'public');

            $photo = $request->photos()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'caption' => $caption,
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
                'accuracy_m' => $accuracy,
                'geo_source' => $source,
                'captured_at' => $geo['captured_at'] ?? null,
                'uploaded_by' => $actor->id,
            ]);

            $this->syncGeometry($photo);

            $this->log($request, $actor, 'photo_added', $request->status, $request->status,
                sprintf('Geo-tagged photo added (%s, %.5f, %.5f).', $source, $geo['latitude'], $geo['longitude']));

            return $photo;
        });
    }

    /** Completion is blocked until at least one geo-tagged verification photograph exists. */
    public function complete(ShelterRepairRequest $request, User $actor, ?string $notes = null): ShelterRepairRequest
    {
        return DB::transaction(function () use ($request, $actor, $notes) {
            $request = $this->lock($request);
            $this->guardTransition($request, RepairStatus::Completed);

            if ($request->hasMaterials() && ! $request->allMaterialsIssued()) {
                throw ValidationException::withMessages(['complete' => 'All planned materials must be issued before the repair can be completed.']);
            }
            if (! $request->hasGeoTaggedPhoto()) {
                throw ValidationException::withMessages(['complete' => 'A geo-tagged verification photograph is required before the repair can be closed.']);
            }

            $request->fill([
                'completed_by' => $actor->id,
                'completed_at' => now(),
                'completion_notes' => $notes,
            ]);

            return $this->move($request, RepairStatus::Completed, $actor, 'completed', $notes);
        });
    }

    // --- GPS helpers ---------------------------------------------------

    /**
     * Read GPS coordinates from a JPEG's EXIF data.
     *
     * @return array{latitude:float, longitude:float, captured_at:?\Illuminate\Support\Carbon}|null
     */
    public function extractExifGps(?string $path): ?array
    {
        if ($path === null || ! function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($path, 'ANY_TAG', true);
        } catch (\Throwable) {
            return null;
        }

        $gps = is_array($exif) ? ($exif['GPS'] ?? null) : null;
        if (! is_array($gps) || empty($gps['GPSLatitude']) || empty($gps['GPSLongitude'])) {
            return null;
        }

        $lat = $this->dmsToDecimal($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
        $lng = $this->dmsToDecimal($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');
        if ($lat === null || $lng === null || ! $this->validCoordinates($lat, $lng)) {
            return null;
        }
        // 0,0 is what many cameras write when they have no fix.
        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) {
            return null;
        }

        $capturedAt = null;
        if (! empty($exif['EXIF']['DateTimeOriginal'])) {
            try {
                $capturedAt = \Illuminate\Support\Carbon::createFromFormat('Y:m:d H:i:s', $exif['EXIF']['DateTimeOriginal']);
            } catch (\Throwable) {
                $capturedAt = null;
            }
        }

        return ['latitude' => $lat, 'longitude' => $lng, 'captured_at' => $capturedAt];
    }

    /** @param array<int,string> $dms three rationals such as ["23/1","48/1","3000/100"] */
    public function dmsToDecimal(array $dms, string $ref): ?float
    {
        if (count($dms) < 3) {
            return null;
        }
        $parts = array_map(fn ($v) => $this->rational($v), array_slice($dms, 0, 3));
        if (in_array(null, $parts, true)) {
            return null;
        }

        $decimal = $parts[0] + $parts[1] / 60 + $parts[2] / 3600;

        return in_array(strtoupper($ref), ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private function rational(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value) && str_contains($value, '/')) {
            [$n, $d] = array_pad(explode('/', $value, 2), 2, '1');
            if (is_numeric($n) && is_numeric($d) && (float) $d != 0.0) {
                return (float) $n / (float) $d;
            }
        }

        return null;
    }

    public function validCoordinates(mixed $lat, mixed $lng): bool
    {
        return is_numeric($lat) && is_numeric($lng)
            && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    // --- Internals -----------------------------------------------------

    private function lock(ShelterRepairRequest $request): ShelterRepairRequest
    {
        return ShelterRepairRequest::query()->lockForUpdate()->findOrFail($request->id);
    }

    private function guardTransition(ShelterRepairRequest $request, RepairStatus $next): void
    {
        if (! $request->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "A {$request->status->label()} request cannot move to {$next->label()}.",
            ]);
        }
    }

    private function move(ShelterRepairRequest $request, RepairStatus $next, User $actor, string $action, ?string $note): ShelterRepairRequest
    {
        $from = $request->status;
        $request->status = $next;
        $request->save();
        $this->log($request, $actor, $action, $from, $next, $note);

        return $request;
    }

    private function log(ShelterRepairRequest $request, User $actor, string $action, ?RepairStatus $from, ?RepairStatus $to, ?string $note): void
    {
        ShelterRepairEvent::create([
            'shelter_repair_request_id' => $request->id,
            'user_id' => $actor->id,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'note' => $note,
        ]);
    }

    /** Post the issue to the Feature 5 stock ledger, when that feature is installed. */
    private function postToLedger(int $batchId, float $quantity, User $actor, ShelterRepairRequest $request, ShelterRepairMaterial $material): void
    {
        $stockService = 'App\\Services\\StockService';
        $batchModel = 'App\\Models\\StockBatch';

        if (! class_exists($stockService) || ! class_exists($batchModel) || ! Schema::hasTable((new $batchModel)->getTable())) {
            throw new DomainException('The stock module is not installed; issue the material without a stock batch.');
        }

        $batch = $batchModel::query()->findOrFail($batchId);
        app($stockService)->issue($batch, $quantity, $actor, $request, "Materials for repair {$request->code}: {$material->item_name}");
    }

    /** Keep the optional PostGIS geography column in step with latitude/longitude. */
    private function syncGeometry(ShelterVerificationPhoto $photo): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasColumn('shelter_verification_photos', 'geom')) {
            return;
        }

        DB::statement(
            'UPDATE shelter_verification_photos SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$photo->longitude, $photo->latitude, $photo->id]
        );
    }
}
