<x-app-layout :title="$incident->code">
    <x-slot name="header">
        <div>
            <h1>{{ $incident->code }} · {{ $incident->title }} <x-badge :status="$incident->status" class="align-middle fs-6" /></h1>
            <div class="text-muted small">{{ $incident->hazard_type->label() }} · Block {{ $incident->block->display_name }} · occurred {{ $incident->occurred_at->format('d M Y H:i') }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('investigate', $incident)
                <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#investigateModal">Start investigation</button>
            @endcan
            @can('respond', $incident)
                <button type="button" class="btn btn-rg btn-sm" data-bs-toggle="modal" data-bs-target="#respondModal">Start response</button>
            @endcan
            @can('close', $incident)
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#closeModal">Verify &amp; close</button>
            @endcan
            @can('addNote', $incident)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#noteModal">Add note</button>
            @endcan
            <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary btn-sm">All incidents</a>
        </div>
    </x-slot>

    @php($steps = \App\Enums\IncidentStatus::cases())
    @php($currentIdx = array_search($incident->status, $steps, true))
    <div class="card shadow-sm mb-4"><div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-2 small">
            @foreach($steps as $i => $step)
                <span class="badge rounded-pill {{ $i <= $currentIdx ? 'bg-success' : 'bg-light text-muted border' }}">{{ $i + 1 }}. {{ $step->label() }}</span>
                @if(! $loop->last)<span class="text-muted">→</span>@endif
            @endforeach
        </div>
    </div></div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Report</div>
                <div class="card-body small">
                    <p>{{ $incident->description }}</p>
                    <dl class="row mb-0">
                        <dt class="col-5">Reported by</dt><dd class="col-7">{{ $incident->reportedBy?->name ?? '—' }} · {{ $incident->created_at->format('d M Y H:i') }}</dd>
                        <dt class="col-5">Linked alert</dt><dd class="col-7">@if($incident->hazardAlert)<a href="{{ route('alerts.show', $incident->hazardAlert) }}">{{ $incident->hazardAlert->title }}</a>@else —@endif</dd>
                        <dt class="col-5">Households affected</dt><dd class="col-7">{{ $incident->households_affected ?? '—' }}</dd>
                        <dt class="col-5">People affected</dt><dd class="col-7">{{ $incident->people_affected ?? '—' }}</dd>
                        <dt class="col-5">Injuries / deaths</dt><dd class="col-7">{{ $incident->injuries ?? '—' }} / {{ $incident->deaths ?? '—' }}</dd>
                        <dt class="col-5">Evacuation site</dt><dd class="col-7">@if($incident->evacuationSite)<a href="{{ route('sites.show', $incident->evacuationSite) }}">{{ $incident->evacuationSite->code }} · {{ $incident->evacuationSite->name }}</a>@else —@endif</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Investigation</div>
                <div class="card-body small">
                    @if($incident->investigation_started_at)
                        <div class="text-muted mb-1">Started {{ $incident->investigation_started_at->format('d M Y H:i') }} by {{ $incident->investigatedBy?->name }}</div>
                        <p class="mb-0">{{ $incident->investigation_notes }}</p>
                    @else
                        <span class="text-muted">Not started.</span>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Response</div>
                <div class="card-body small">
                    @if($incident->response_started_at)
                        <div class="text-muted mb-1">Started {{ $incident->response_started_at->format('d M Y H:i') }} · lead {{ $incident->responseLead?->name }}</div>
                        <p class="mb-0">{{ $incident->response_notes }}</p>
                    @else
                        <span class="text-muted">Not started.</span>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Verified closure</div>
                <div class="card-body small">
                    @if($incident->closed_at)
                        <div class="text-success mb-1">Verified and closed {{ $incident->closed_at->format('d M Y H:i') }} by {{ $incident->verifiedBy?->name }}</div>
                        <p class="mb-0">{{ $incident->verification_notes }}</p>
                    @else
                        <span class="text-muted">Open. Closure requires a coordinator to verify the response.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Timeline</div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        @foreach($incident->updates as $u)
                            <li>
                                <div class="d-flex justify-content-between">
                                    <span>
                                        @if($u->to_status && $u->to_status !== $u->from_status)
                                            <span class="badge {{ \App\Enums\IncidentStatus::from($u->to_status)->badgeClass() }} me-1">{{ \App\Enums\IncidentStatus::from($u->to_status)->label() }}</span>
                                        @else
                                            <span class="badge bg-secondary me-1">note</span>
                                        @endif
                                        {{ $u->note }}
                                    </span>
                                    <span class="small text-muted text-nowrap ms-2">{{ $u->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="small text-muted">by {{ $u->user?->name ?? 'system' }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @can('investigate', $incident)
        <x-modal-form id="investigateModal" title="Start investigation" :action="route('incidents.investigate', $incident)" submit="Start investigation">
            <label for="investigation_notes" class="form-label required">Initial findings / plan</label>
            <textarea id="investigation_notes" name="investigation_notes" rows="4" class="form-control" required minlength="10">{{ old('investigation_notes') }}</textarea>
        </x-modal-form>
    @endcan

    @can('respond', $incident)
        <x-modal-form id="respondModal" title="Start response" :action="route('incidents.respond', $incident)" submit="Start response">
            <div class="mb-3">
                <label for="response_notes" class="form-label required">Response actions</label>
                <textarea id="response_notes" name="response_notes" rows="3" class="form-control" required minlength="10">{{ old('response_notes') }}</textarea>
            </div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label">Households affected</label><input name="households_affected" type="number" min="0" class="form-control" value="{{ old('households_affected', $incident->households_affected) }}"></div>
                <div class="col-6"><label class="form-label">People affected</label><input name="people_affected" type="number" min="0" class="form-control" value="{{ old('people_affected', $incident->people_affected) }}"></div>
                <div class="col-6"><label class="form-label">Injuries</label><input name="injuries" type="number" min="0" class="form-control" value="{{ old('injuries', $incident->injuries) }}"></div>
                <div class="col-6"><label class="form-label">Deaths</label><input name="deaths" type="number" min="0" class="form-control" value="{{ old('deaths', $incident->deaths) }}"></div>
                <div class="col-12">
                    <label for="evacuation_site_id" class="form-label">Evacuation site used</label>
                    <select id="evacuation_site_id" name="evacuation_site_id" class="form-select">
                        <option value="">— none —</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('evacuation_site_id', $incident->evacuation_site_id) == $site->id)>{{ $site->code }} · {{ $site->name }} ({{ $site->availableCapacity() }} free)</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-modal-form>
    @endcan

    @can('close', $incident)
        <x-modal-form id="closeModal" title="Verify response and close" :action="route('incidents.close', $incident)" submit="Verify &amp; close" submit-class="btn-success">
            <label for="verification_notes" class="form-label required">Verification notes (what was checked and by whom)</label>
            <textarea id="verification_notes" name="verification_notes" rows="4" class="form-control" required minlength="10">{{ old('verification_notes') }}</textarea>
        </x-modal-form>
    @endcan

    @can('addNote', $incident)
        <x-modal-form id="noteModal" title="Add note" :action="route('incidents.note', $incident)" submit="Add note">
            <textarea name="note" rows="3" class="form-control" required maxlength="2000" placeholder="Update, observation, request…">{{ old('note') }}</textarea>
        </x-modal-form>
    @endcan
</x-app-layout>
