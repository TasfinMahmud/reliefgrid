<?php

namespace App\Http\Controllers;

use App\Enums\RepairStatus;
use App\Http\Requests\Shelter\ApproveRepairRequest;
use App\Http\Requests\Shelter\AssessRepairRequest;
use App\Http\Requests\Shelter\CompleteRepairRequest;
use App\Http\Requests\Shelter\IssueMaterialRequest;
use App\Http\Requests\Shelter\RejectRepairRequest;
use App\Http\Requests\Shelter\StoreRepairRequest;
use App\Http\Requests\Shelter\VerificationPhotoRequest;
use App\Models\Block;
use App\Models\Household;
use App\Models\ShelterRepairMaterial;
use App\Models\ShelterRepairRequest;
use App\Services\ShelterRepairService;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Feature 12 – thin controller: authorise (Policy), validate (Form Request), delegate to the service.
 */
class ShelterRepairController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ShelterRepairService $repairs)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ShelterRepairRequest::class);

        $query = ShelterRepairRequest::query()->with(['household', 'block']);

        if ($status = $request->query('status')) {
            if (RepairStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }
        if ($blockId = $request->integer('block_id')) {
            $query->where('block_id', $blockId);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('household', fn ($h) => $h->where('head_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        return view('repairs.index', [
            'repairs' => $query->latest('id')->paginate(15)->withQueryString(),
            'statuses' => RepairStatus::options(),
            'blocks' => Block::query()->orderBy('code')->get(),
            'filters' => $request->only(['status', 'block_id', 'q']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ShelterRepairRequest::class);

        return view('repairs.create', [
            'households' => Household::query()->active()->with('block')->orderBy('code')->get(),
            'damageTypes' => ShelterRepairRequest::DAMAGE_TYPES,
            'selectedHousehold' => $request->integer('household_id') ?: null,
        ]);
    }

    public function store(StoreRepairRequest $request): RedirectResponse
    {
        $this->authorize('create', ShelterRepairRequest::class);

        $repair = $this->repairs->submit($request->validated(), $request->user());

        return redirect()->route('repairs.show', $repair)->with('success', "Repair request {$repair->code} submitted.");
    }

    public function show(ShelterRepairRequest $repair): View
    {
        $this->authorize('view', $repair);

        $repair->load(['household.block', 'block', 'materials', 'photos.uploader', 'events.user', 'reporter', 'assessor', 'approver', 'rejecter', 'completer']);

        return view('repairs.show', [
            'repair' => $repair,
            'severities' => ShelterRepairRequest::SEVERITIES,
            'stockBatches' => $this->stockBatches(),
            'hasGeoPhoto' => $repair->photos->isNotEmpty(),
        ]);
    }

    public function assess(AssessRepairRequest $request, ShelterRepairRequest $repair): RedirectResponse
    {
        $this->authorize('assess', $repair);
        $this->repairs->assess($repair, $request->validated(), $request->user());

        return $this->back($repair, 'Assessment recorded. The request is waiting for approval.');
    }

    public function approve(ApproveRepairRequest $request, ShelterRepairRequest $repair): RedirectResponse
    {
        $this->authorize('approve', $repair);
        $this->repairs->approve($repair, $request->user(), $request->validated()['note'] ?? null);

        return $this->back($repair, 'Repair approved.');
    }

    public function reject(RejectRepairRequest $request, ShelterRepairRequest $repair): RedirectResponse
    {
        $this->authorize('reject', $repair);
        $this->repairs->reject($repair, $request->validated()['reason'], $request->user());

        return $this->back($repair, 'Repair request rejected.');
    }

    public function issueMaterial(IssueMaterialRequest $request, ShelterRepairRequest $repair, ShelterRepairMaterial $material): RedirectResponse
    {
        $this->authorize('issueMaterials', $repair);
        abort_unless($material->shelter_repair_request_id === $repair->id, 404);

        $data = $request->validated();

        try {
            $this->repairs->issueMaterial($material, (float) $data['quantity'], $request->user(), $data['stock_batch_id'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return $this->back($repair, "Issued {$material->item_name}.");
    }

    public function addPhoto(VerificationPhotoRequest $request, ShelterRepairRequest $repair): RedirectResponse
    {
        $this->authorize('addPhoto', $repair);

        $this->repairs->addPhoto(
            $repair,
            $request->file('photo'),
            $request->user(),
            [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'accuracy' => $request->input('accuracy'),
                'source' => $request->input('geo_source'),
            ],
            $request->input('caption')
        );

        return $this->back($repair, 'Geo-tagged verification photo added.');
    }

    public function complete(CompleteRepairRequest $request, ShelterRepairRequest $repair): RedirectResponse
    {
        $this->authorize('complete', $repair);
        $this->repairs->complete($repair, $request->user(), $request->validated()['notes'] ?? null);

        return $this->back($repair, 'Repair completed and closed.');
    }

    private function back(ShelterRepairRequest $repair, string $message): RedirectResponse
    {
        return redirect()->route('repairs.show', $repair)->with('success', $message);
    }

    /** Stock batches for the "issue from" dropdown; empty when the stock feature (Feature 5) is not installed. */
    private function stockBatches()
    {
        $model = 'App\\Models\\StockBatch';
        if (! class_exists($model) || ! Schema::hasTable((new $model)->getTable())) {
            return collect();
        }

        return $model::query()->orderBy('id')->limit(200)->get();
    }
}
