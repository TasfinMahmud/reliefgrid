<x-app-layout :title="'Issue stock · '.$warehouse->code">
    <x-slot name="header"><h1>Issue stock from {{ $warehouse->code }} · {{ $warehouse->name }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        @if($batches->isEmpty())
            <p class="text-muted mb-0">No unexpired batches with stock in this warehouse.</p>
        @else
            <form method="POST" action="{{ route('stock.issue', $warehouse) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="stock_batch_id" class="form-label required">Batch <span class="text-muted">(FEFO order — issue the first listed batch for an item when possible)</span></label>
                        <select id="stock_batch_id" name="stock_batch_id" class="form-select @error('stock_batch_id') is-invalid @enderror" required>
                            <option value="">Select batch…</option>
                            @foreach($batches as $row)
                                <option value="{{ $row['batch']->id }}" @selected(old('stock_batch_id') == $row['batch']->id)>
                                    {{ $row['batch']->item->name }} — batch {{ $row['batch']->batch_number }} — {{ number_format($row['balance'], 2) }} {{ $row['batch']->item->unit }} available — expires {{ $row['batch']->expiry_date?->format('d M Y') ?? 'n/a' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="stock_batch_id" />
                    </div>
                    <div class="col-md-4">
                        <label for="quantity" class="form-label required">Quantity</label>
                        <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required>
                        <x-input-error for="quantity" />
                    </div>
                    <div class="col-md-6">
                        <label for="assistance_cycle_id" class="form-label">For assistance cycle</label>
                        <select id="assistance_cycle_id" name="assistance_cycle_id" class="form-select">
                            <option value="">— none / other purpose —</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected(old('assistance_cycle_id') == $cycle->id)>{{ $cycle->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="note" class="form-label required">Purpose / note</label>
                        <input id="note" name="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}" required maxlength="255" placeholder="e.g. Distribution point DP-3, 12 Nov">
                        <x-input-error for="note" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-danger">Issue stock</button>
                    <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        @endif
    </div></div>
</x-app-layout>
