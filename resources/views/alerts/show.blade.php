<x-app-layout :title="$alert->title">
    <x-slot name="header">
        <div>
            <h1><x-badge :status="$alert->severity" class="align-middle fs-6" /> {{ $alert->title }} <x-badge :status="$alert->effectiveStatus()" class="align-middle fs-6" /></h1>
            <div class="text-muted small">{{ $alert->hazard_type->label() }} · issued {{ $alert->issued_at->format('d M Y H:i') }} by {{ $alert->issuedBy?->name }}</div>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Models\Incident::class)
                @if($alert->isCurrentlyActive())
                    <a href="{{ route('incidents.create', ['hazard_alert_id' => $alert->id]) }}" class="btn btn-outline-secondary btn-sm">Report linked incident</a>
                @endif
            @endcan
            @can('cancel', $alert)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel alert</button>
            @endcan
            <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary btn-sm">All alerts</a>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <p>{{ $alert->message }}</p>
                    @if($alert->instructions)
                        <h2 class="h6">Instructions</h2>
                        <p class="mb-0">{{ $alert->instructions }}</p>
                    @endif
                </div>
            </div>
            @if($alert->incidents->isNotEmpty())
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Linked incidents</div>
                    <ul class="list-group list-group-flush">
                        @foreach($alert->incidents as $incident)
                            <li class="list-group-item d-flex justify-content-between">
                                <span><a href="{{ route('incidents.show', $incident) }}">{{ $incident->code }}</a> {{ $incident->title }} · {{ $incident->block->code }}</span>
                                <x-badge :status="$incident->status" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-4">Blocks</dt><dd class="col-8">{{ $alert->blocks->map(fn ($b) => $b->display_name)->join(', ') }}</dd>
                        <dt class="col-4">Expires</dt><dd class="col-8">{{ $alert->expires_at?->format('d M Y H:i') ?? 'Until cancelled' }}</dd>
                        @if($alert->cancelled_at)
                            <dt class="col-4">Cancelled</dt><dd class="col-8">{{ $alert->cancelled_at->format('d M Y H:i') }} by {{ $alert->cancelledBy?->name }} — {{ $alert->cancel_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @can('cancel', $alert)
        <x-modal-form id="cancelModal" title="Cancel alert" :action="route('alerts.cancel', $alert)" submit="Cancel alert" submit-class="btn-danger">
            <label for="cancel_reason" class="form-label required">Reason</label>
            <input id="cancel_reason" name="cancel_reason" class="form-control" required maxlength="255" placeholder="e.g. Water levels receded">
        </x-modal-form>
    @endcan
</x-app-layout>
