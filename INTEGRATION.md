# Feature 12 – Shelter repair workflow: how to add it to the project

The files in this folder use the **same paths as the Laravel project**. Copy the folders `app`, `database`,
`docs`, `resources`, `routes` and `tests` into the root of the ReliefGrid project and accept "merge folders".
No existing file is overwritten (every file here is new).

## Steps
```powershell
# 1. copy the folders (from this folder) into the project root, then:
cd path\to\reliefgrid
php artisan migrate            # creates the 4 shelter_* tables
php artisan db:seed --class="Database\Seeders\Features\Feature12ShelterRepairSeeder"   # optional demo data
php artisan storage:link       # once, so uploaded photos are visible
php artisan test --filter=Shelter
php artisan test --filter=RepairStatus
```
Routes (`routes/features/shelter.php`), the navigation entry, the dashboard widget and the seeder are picked up
automatically (see CONTRIBUTING.md). Log in as `assessor@reliefgrid.test` / `password` and open *Shelter repairs*.

PHP needs the **exif** extension to read GPS from photos (in Laravel Herd: enable `exif` in php.ini,
`extension=exif`). Without it, photos are still accepted when the device location button was used, and the two
EXIF tests are skipped.

## What the code assumes about the rest of the project
These come from the shared code the README/CONTRIBUTING describe; if one differs, adjust that one line:
* `App\Models\User` has `isAdmin() isManager() isFieldOfficer() isShelterAssessor() isWarehouseOfficer()`.
* `App\Models\Household` (has `block`, `code`, `head_name`, scope `active()`), `App\Models\Block` (`code`).
* `App\Dashboard\AbstractWidget` with `order() cards() actions() view() width() data()`.
* Layout component `<x-app-layout title="…">` with a `header` slot; routes `households.show`.
* Optional (Feature 5): `App\Services\StockService::issue($batch, $qty, $user, $subject, $note)` and
  `App\Models\StockBatch`. Without them, materials are recorded without a ledger posting.

## Optional links from the household page (two tiny edits in shared files, your region only)
`app/Models/Household.php` – add the relation:
```php
public function repairRequests(): HasMany
{
    return $this->hasMany(ShelterRepairRequest::class);
}
```
`resources/views/households/show.blade.php` – a button next to the other actions:
```blade
@can('create', \App\Models\ShelterRepairRequest::class)
    @if($household->isActive())
        <a href="{{ route('repairs.create', ['household_id' => $household->id]) }}" class="btn btn-outline-secondary btn-sm">Repair request</a>
    @endif
@endcan
```
Also update the README feature table (Feature 12 → implemented) as CONTRIBUTING.md asks.
