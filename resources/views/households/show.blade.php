<x-app-layout :title="$household->code">
    <x-slot name="header">
        <div>
            <h1>{{ $household->code }} <x-badge :status="$household->status" class="align-middle fs-6" /></h1>
            <div class="text-muted small">{{ $household->head_name }} · Block {{ $household->block->display_name }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('update', $household)
                <a href="{{ route('households.edit', $household) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endcan
            @can('deactivate', $household)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deactivateModal">Deactivate</button>
            @endcan
            @can('reactivate', $household)
                <x-confirm-form :action="route('households.reactivate', $household)" confirm="Reactivate this household?">
                    <button class="btn btn-outline-success btn-sm">Reactivate</button>
                </x-confirm-form>
            @endcan
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Household details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Head of household</dt><dd class="col-7">{{ $household->head_name }} @if($household->head_sex)({{ ucfirst($household->head_sex) }}@if($household->head_age), {{ $household->head_age }}@endif)@endif</dd>
                        <dt class="col-5">Household size</dt><dd class="col-7">{{ $household->household_size }}</dd>
                        <dt class="col-5">Block / shelter</dt><dd class="col-7">{{ $household->block->display_name }}{{ $household->shelter_number ? ' · '.$household->shelter_number : '' }}</dd>
                        <dt class="col-5">Phone</dt><dd class="col-7">{{ $household->phone ?? '—' }}</dd>
                        <dt class="col-5">Vulnerability notes</dt><dd class="col-7">{{ $household->vulnerability_notes ?? '—' }}</dd>
                        <dt class="col-5">Consent</dt>
                        <dd class="col-7">
                            @if($household->consent_given)
                                <span class="text-success">Recorded</span> {{ $household->consent_given_at?->format('d M Y H:i') }} ({{ $household->consent_version }})
                            @else
                                <span class="text-danger">Not recorded</span>
                            @endif
                        </dd>
                        <dt class="col-5">Registered</dt><dd class="col-7">{{ $household->created_at->format('d M Y H:i') }} by {{ $household->registeredBy?->name ?? '—' }}</dd>
                        @if(! $household->isActive())
                            <dt class="col-5 text-danger">Deactivated</dt>
                            <dd class="col-7">{{ $household->deactivated_at?->format('d M Y H:i') }} by {{ $household->deactivatedBy?->name ?? '—' }}<br><em>{{ $household->deactivation_reason }}</em></dd>
                        @endif
                    </dl>
                </div>
            </div>

        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Tokens</span>
                    <div class="d-flex gap-2">
                        @can('issueToken', $household)
                            @if(! $household->activeToken)
                                <x-confirm-form :action="route('households.tokens.store', $household)" confirm="Issue a new token to this household?">
                                    <button class="btn btn-rg btn-sm">Issue token</button>
                                </x-confirm-form>
                            @endif
                        @endcan
                        @can('reissueToken', $household)
                            @if($household->activeToken)
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#reissueModal">Report lost / reissue</button>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Token</th><th>Status</th><th>Issued</th><th>Invalidated</th><th></th></tr></thead>
                        <tbody>
                        @forelse($household->tokens as $token)
                            <tr class="{{ $token->isActive() ? 'table-success' : '' }}">
                                <td>
                                    <code>{{ $token->token_uid }}</code>
                                    @if($token->replaces_token_id)<div class="small text-muted">replaces …{{ substr((string) $token->replaces?->token_uid, -6) }}</div>@endif
                                </td>
                                <td><x-badge :status="$token->status" /></td>
                                <td class="small">{{ $token->issued_at->format('d M Y H:i') }}<br><span class="text-muted">{{ $token->issuedBy?->name }}</span></td>
                                <td class="small">
                                    @if($token->invalidated_at)
                                        {{ $token->invalidated_at->format('d M Y H:i') }}<br><span class="text-muted">{{ $token->invalidatedBy?->name }}: {{ $token->invalidation_reason }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('tokens.card', $token) }}" class="btn btn-sm btn-outline-secondary">Card</a>
                                    @if($token->isActive())
                                        @can('revokeToken', $household)
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#revokeModal{{ $token->id }}">Revoke</button>
                                            <x-modal-form id="revokeModal{{ $token->id }}" title="Revoke token …{{ $token->shortUid() }}" :action="route('tokens.revoke', $token)" submit="Revoke" submit-class="btn-danger">
                                                <label for="revoke_reason_{{ $token->id }}" class="form-label required">Reason</label>
                                                <input id="revoke_reason_{{ $token->id }}" name="reason" class="form-control" required maxlength="255">
                                            </x-modal-form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No token issued yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">History</div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        @foreach($household->events as $event)
                            <li>
                                <div class="d-flex justify-content-between">
                                    <span><span class="badge {{ $event->badgeClass() }} me-1">{{ str_replace('_', ' ', $event->event_type) }}</span>{{ $event->description }}</span>
                                    <span class="small text-muted text-nowrap ms-2">{{ $event->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="small text-muted">by {{ $event->user?->name ?? 'system' }}</div>
                                @if($event->changes)
                                    <div class="small text-muted">Changed: {{ collect($event->changes)->map(fn ($v, $k) => "$k → ".(is_scalar($v) ? $v : json_encode($v)))->join('; ') }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @can('deactivate', $household)
        <x-modal-form id="deactivateModal" title="Deactivate household {{ $household->code }}" :action="route('households.deactivate', $household)" submit="Deactivate" submit-class="btn-danger">
            <p class="small text-muted">The household will no longer receive entitlements and its active token will be revoked. The record and its history are preserved.</p>
            <label for="deactivate_reason" class="form-label required">Reason</label>
            <input id="deactivate_reason" name="reason" class="form-control" required maxlength="255" placeholder="e.g. Relocated to another camp">
        </x-modal-form>
    @endcan

    @can('reissueToken', $household)
        <x-modal-form id="reissueModal" title="Reissue token" :action="route('households.tokens.reissue', $household)" submit="Reissue token" submit-class="btn-warning">
            <p class="small text-muted">The current token will be invalidated and kept in the history; a new opaque token will be issued.</p>
            <div class="mb-3">
                <label class="form-label required">What happened to the current token?</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="outcome" id="outcome_lost" value="lost" checked><label class="form-check-label" for="outcome_lost">Lost or damaged</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="outcome" id="outcome_revoked" value="revoked"><label class="form-check-label" for="outcome_revoked">Revoked (misuse / suspected fraud)</label></div>
            </div>
            <label for="reissue_reason" class="form-label required">Reason / notes</label>
            <input id="reissue_reason" name="reason" class="form-control" required maxlength="255">
        </x-modal-form>
    @endcan
</x-app-layout>
