@php
    $steps = [
        \App\Enums\RepairStatus::Submitted, \App\Enums\RepairStatus::Assessed, \App\Enums\RepairStatus::Approved,
        \App\Enums\RepairStatus::MaterialsIssued, \App\Enums\RepairStatus::Completed,
    ];
    $rank = array_search($repair->status, $steps, true);
    $rejected = $repair->status === \App\Enums\RepairStatus::Rejected;
@endphp
<x-app-layout :title="'Repair '.$repair->code">
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3 mb-0">
                {{ $repair->code }}
                <span class="badge {{ $repair->status->badgeClass() }} ms-2">{{ $repair->status->label() }}</span>
            </h1>
            <a href="{{ route('repairs.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success" data-autodismiss>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Workflow progress --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between text-center">
                @foreach($steps as $i => $step)
                    @php
                        $done = ! $rejected && $rank !== false && $i <= $rank;
                    @endphp
                    <div class="flex-fill">
                        <div class="rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center {{ $done ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width:34px;height:34px;">
                            {{ $done ? '✓' : $i + 1 }}
                        </div>
                        <div class="small {{ $done ? 'fw-semibold' : 'text-muted' }}">{{ $step->label() }}</div>
                    </div>
                @endforeach
            </div>
            @if($rejected)
                <div class="alert alert-danger mt-3 mb-0">
                    <strong>Rejected</strong> by {{ $repair->rejecter?->name ?? 'unknown' }} on {{ $repair->rejected_at?->format('d M Y H:i') }}:
                    {{ $repair->rejection_reason }}
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT: facts, materials, photos, timeline --}}
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Request details</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Household</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('households.show', $repair->household) }}">{{ $repair->household->code }}</a>
                            · {{ $repair->household->head_name }}
                        </dd>
                        <dt class="col-sm-4">Block</dt><dd class="col-sm-8">{{ $repair->block->code }}</dd>
                        <dt class="col-sm-4">Damage type</dt><dd class="col-sm-8">{{ $repair->damageTypeLabel() }}</dd>
                        <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $repair->description }}</dd>
                        <dt class="col-sm-4">Reported by</dt><dd class="col-sm-8">{{ $repair->reporter?->name ?? '—' }} · {{ $repair->created_at->format('d M Y H:i') }}</dd>
                        @if($repair->assessed_at)
                            <dt class="col-sm-4">Assessment</dt>
                            <dd class="col-sm-8">
                                {{ $repair->severityLabel() }} severity
                                @if($repair->estimated_days) · about {{ $repair->estimated_days }} day(s) @endif
                                · {{ $repair->assessor?->name }}
                                @if($repair->assessment_notes)<div class="text-muted small">{{ $repair->assessment_notes }}</div>@endif
                            </dd>
                        @endif
                        @if($repair->approved_at)
                            <dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $repair->approver?->name }} · {{ $repair->approved_at->format('d M Y H:i') }}</dd>
                        @endif
                        @if($repair->completed_at)
                            <dt class="col-sm-4">Completed</dt>
                            <dd class="col-sm-8">
                                {{ $repair->completer?->name }} · {{ $repair->completed_at->format('d M Y H:i') }}
                                @if($repair->completion_notes)<div class="text-muted small">{{ $repair->completion_notes }}</div>@endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Materials --}}
            @if($repair->materials->isNotEmpty())
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Materials</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th class="text-end">Required</th><th class="text-end">Issued</th><th style="min-width:260px">Issue</th></tr>
                            </thead>
                            <tbody>
                                @foreach($repair->materials as $material)
                                    <tr>
                                        <td>{{ $material->item_name }}</td>
                                        <td class="text-end">{{ number_format((float) $material->quantity_required, 2) }} {{ $material->unit }}</td>
                                        <td class="text-end">
                                            {{ number_format((float) $material->quantity_issued, 2) }}
                                            @if($material->isFullyIssued())<span class="badge text-bg-success">done</span>@endif
                                        </td>
                                        <td>
                                            @can('issueMaterials', $repair)
                                                @unless($material->isFullyIssued())
                                                    <form method="POST" action="{{ route('repairs.materials.issue', [$repair, $material]) }}" class="d-flex gap-1">
                                                        @csrf
                                                        <input type="number" step="0.01" min="0.01" max="{{ $material->remaining() }}" name="quantity"
                                                               value="{{ $material->remaining() }}" class="form-control form-control-sm" style="width:90px" required>
                                                        @if($stockBatches->isNotEmpty())
                                                            <select name="stock_batch_id" class="form-select form-select-sm" required>
                                                                <option value="">Stock batch…</option>
                                                                @foreach($stockBatches as $batch)
                                                                    <option value="{{ $batch->id }}">#{{ $batch->id }}@if(method_exists($batch, 'balance')) · bal {{ $batch->balance() }}@endif</option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                        <button class="btn btn-sm btn-outline-primary">Issue</button>
                                                    </form>
                                                @endunless
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Verification photos --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold d-flex justify-content-between">
                    <span>Geo-tagged verification photos</span>
                    <span class="badge {{ $hasGeoPhoto ? 'text-bg-success' : 'text-bg-warning' }}">{{ $hasGeoPhoto ? 'closure unlocked' : 'required to close' }}</span>
                </div>
                <div class="card-body">
                    @forelse($repair->photos as $photo)
                        <div class="d-flex gap-3 mb-3">
                            <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                <img src="{{ $photo->url() }}" alt="Verification photo" class="rounded border" style="width:120px;height:90px;object-fit:cover">
                            </a>
                            <div class="small">
                                <div>{{ $photo->caption ?: $photo->original_name }}</div>
                                <div class="text-muted">
                                    {{ number_format($photo->latitude, 6) }}, {{ number_format($photo->longitude, 6) }}
                                    · from {{ $photo->geo_source === 'exif' ? 'photo GPS (EXIF)' : 'device location' }}
                                    @if($photo->accuracy_m) (±{{ round($photo->accuracy_m) }} m)@endif
                                </div>
                                <div class="text-muted">{{ $photo->uploader?->name }} · {{ $photo->created_at->format('d M Y H:i') }}
                                    · <a href="{{ $photo->mapUrl() }}" target="_blank" rel="noopener">view on map</a></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No photo yet. The repair cannot be closed until one geo-tagged photo is attached.</p>
                    @endforelse

                    @can('addPhoto', $repair)
                        <hr>
                        <form method="POST" action="{{ route('repairs.photos.store', $repair) }}" enctype="multipart/form-data"
                              x-data="{ lat: '', lng: '', acc: '', src: '', msg: '',
                                        locate() {
                                            if (!navigator.geolocation) { this.msg = 'Geolocation is not supported by this browser.'; return; }
                                            this.msg = 'Requesting location…';
                                            navigator.geolocation.getCurrentPosition(
                                                (p) => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7);
                                                         this.acc = Math.round(p.coords.accuracy); this.src = 'device';
                                                         this.msg = 'Location captured (±' + this.acc + ' m).'; },
                                                (e) => { this.msg = 'Could not get location: ' + e.message; },
                                                { enableHighAccuracy: true, timeout: 10000 });
                                        } }">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label for="photo" class="form-label">Photograph (JPG/PNG, max 8 MB)</label>
                                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" capture="environment"
                                           class="form-control @error('photo') is-invalid @enderror" required>
                                    @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="caption" class="form-label">Caption</label>
                                    <input type="text" id="caption" name="caption" maxlength="200" class="form-control">
                                </div>
                            </div>
                            <input type="hidden" name="latitude" x-model="lat">
                            <input type="hidden" name="longitude" x-model="lng">
                            <input type="hidden" name="accuracy" x-model="acc">
                            <input type="hidden" name="geo_source" x-model="src">
                            <div class="form-text mt-2">
                                Photos with GPS metadata are geo-tagged automatically. If your photo has none, capture this device's location first.
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="locate()">Use my current location</button>
                                <span class="small text-muted" x-text="msg"></span>
                            </div>
                            <button class="btn btn-primary btn-sm mt-3">Upload photo</button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Timeline</div>
                <ul class="list-group list-group-flush">
                    @foreach($repair->events->sortByDesc('id') as $event)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ \Illuminate\Support\Str::headline($event->action) }}</span>
                                <span class="text-muted small">{{ $event->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="small text-muted">{{ $event->user?->name ?? 'System' }}</div>
                            @if($event->note)<div class="small">{{ $event->note }}</div>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- RIGHT: workflow actions --}}
        <div class="col-lg-5">
            @can('assess', $repair)
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header fw-semibold">Assess damage</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('repairs.assess', $repair) }}"
                              x-data="{ rows: @js(old('materials', [])) }">
                            @csrf
                            <div class="row g-2">
                                <div class="col-7">
                                    <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                                    <select id="severity" name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                        <option value="">Select…</option>
                                        @foreach($severities as $value => $label)
                                            <option value="{{ $value }}" @selected(old('severity') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-5">
                                    <label for="estimated_days" class="form-label">Est. days</label>
                                    <input type="number" min="1" max="365" id="estimated_days" name="estimated_days" value="{{ old('estimated_days') }}" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">Assessment notes</label>
                                    <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 mb-1">
                                <span class="fw-semibold">Materials needed</span>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="rows.push({ item_name: '', unit: '', quantity_required: '' })">+ Add item</button>
                            </div>
                            <template x-for="(row, i) in rows" :key="i">
                                <div class="row g-1 mb-1">
                                    <div class="col-5"><input type="text" class="form-control form-control-sm" placeholder="Item" required
                                            :name="'materials[' + i + '][item_name]'" x-model="row.item_name"></div>
                                    <div class="col-3"><input type="text" class="form-control form-control-sm" placeholder="Unit" required
                                            :name="'materials[' + i + '][unit]'" x-model="row.unit"></div>
                                    <div class="col-3"><input type="number" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="Qty" required
                                            :name="'materials[' + i + '][quantity_required]'" x-model="row.quantity_required"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" @click="rows.splice(i, 1)" aria-label="Remove">&times;</button></div>
                                </div>
                            </template>
                            <p class="small text-muted" x-show="rows.length === 0">No materials listed — the repair needs none.</p>

                            <button class="btn btn-primary mt-3">Save assessment</button>
                        </form>
                    </div>
                </div>
            @endcan

            @can('approve', $repair)
                <div class="card shadow-sm mb-4 border-success">
                    <div class="card-header fw-semibold">Approval</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('repairs.approve', $repair) }}" class="mb-3">
                            @csrf
                            <label for="approve_note" class="form-label">Note (optional)</label>
                            <input type="text" id="approve_note" name="note" maxlength="1000" class="form-control mb-2">
                            <button class="btn btn-success">Approve repair</button>
                        </form>
                    </div>
                </div>
            @endcan

            @can('reject', $repair)
                <div class="card shadow-sm mb-4 border-danger">
                    <div class="card-header fw-semibold">Reject request</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('repairs.reject', $repair) }}">
                            @csrf
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" rows="2" minlength="5" maxlength="1000" class="form-control mb-2" required></textarea>
                            <button class="btn btn-outline-danger">Reject</button>
                        </form>
                    </div>
                </div>
            @endcan

            @can('complete', $repair)
                <div class="card shadow-sm mb-4 border-dark">
                    <div class="card-header fw-semibold">Complete repair</div>
                    <div class="card-body">
                        @unless($hasGeoPhoto)
                            <div class="alert alert-warning py-2">Closing is blocked until a geo-tagged verification photo is uploaded.</div>
                        @endunless
                        @if($repair->materials->isNotEmpty() && ! $repair->allMaterialsIssued())
                            <div class="alert alert-warning py-2">All planned materials must be issued first.</div>
                        @endif
                        <form method="POST" action="{{ route('repairs.complete', $repair) }}">
                            @csrf
                            <label for="completion_notes" class="form-label">Completion notes</label>
                            <textarea id="completion_notes" name="notes" rows="2" maxlength="1000" class="form-control mb-2"></textarea>
                            <button class="btn btn-dark">Mark as completed</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
