<x-app-layout title="Token card">
    <x-slot name="header">
        <h1>Token card</h1>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('households.show', $token->household) }}" class="btn btn-outline-secondary btn-sm">Back to household</a>
            <button type="button" class="btn btn-rg btn-sm" onclick="window.print()">Print</button>
        </div>
    </x-slot>

    @if(! $token->isActive())
        <div class="alert alert-warning no-print">This token is <strong>{{ $token->status->label() }}</strong>{{ $token->invalidation_reason ? ' ('.$token->invalidation_reason.')' : '' }}. Do not hand this card out.</div>
    @endif

    <div class="token-card mx-auto p-4 text-center shadow-sm">
        <div class="fw-bold fs-5 mb-1">ReliefGrid assistance card</div>
        <div class="small text-muted mb-3">{{ $token->household->block->camp ?? 'Settlement' }} · Block {{ $token->household->block->code }}</div>
        <canvas data-qr="{{ $token->token_uid }}" data-qr-size="220" width="220" height="220" aria-label="QR token"></canvas>
        <div class="mt-2"><code class="fs-6">{{ $token->token_uid }}</code></div>
        <hr>
        <div class="row small text-start">
            <div class="col-6"><span class="text-muted">Household</span><br><strong>{{ $token->household->code }}</strong></div>
            <div class="col-6"><span class="text-muted">Members</span><br><strong>{{ $token->household->household_size }}</strong></div>
            <div class="col-6 mt-2"><span class="text-muted">Issued</span><br>{{ $token->issued_at->format('d M Y') }}</div>
            <div class="col-6 mt-2"><span class="text-muted">Status</span><br><x-badge :status="$token->status" /></div>
        </div>
        <div class="small text-muted mt-3">This card carries no personal data. If lost, report it to the registration desk for a replacement.</div>
    </div>
</x-app-layout>
