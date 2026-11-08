<x-app-layout title="Request transfer">
    <x-slot name="header"><h1>Request stock transfer</h1></x-slot>

    <div class="card shadow-sm mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="warehouse_id" class="form-label required">Source warehouse</label>
                <select id="warehouse_id" name="warehouse_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select source…</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($from?->id === $w->id)>{{ $w->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Load batches</button></div>
        </form>
    </div></div>

    @if($from)
        <div class="card shadow-sm"><div class="card-body">
            @if($batches->isEmpty())
                <p class="text-muted mb-0">No unexpired batches with stock in {{ $from->code }}.</p>
            @else
                <form method="POST" action="{{ route('transfers.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="stock_batch_id" class="form-label required">Batch to transfer</label>
                            <select id="stock_batch_id" name="stock_batch_id" class="form-select @error('stock_batch_id') is-invalid @enderror" required>
                                @foreach($batches as $row)
                                    <option value="{{ $row['batch']->id }}" @selected(old('stock_batch_id') == $row['batch']->id)>
                                        {{ $row['batch']->item->name }} — batch {{ $row['batch']->batch_number }} — {{ number_format($row['balance'], 2) }} {{ $row['batch']->item->unit }} — exp. {{ $row['batch']->expiry_date?->format('d M Y') ?? 'n/a' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error for="stock_batch_id" />
                        </div>
                        <div class="col-md-5">
                            <label for="to_warehouse_id" class="form-label required">Destination warehouse</label>
                            <select id="to_warehouse_id" name="to_warehouse_id" class="form-select @error('to_warehouse_id') is-invalid @enderror" required>
                                @foreach($warehouses->where('id', '!=', $from->id) as $w)
                                    <option value="{{ $w->id }}" @selected(old('to_warehouse_id') == $w->id)>{{ $w->display_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="to_warehouse_id" />
                        </div>
                        <div class="col-md-3">
                            <label for="quantity" class="form-label required">Quantity</label>
                            <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required>
                            <x-input-error for="quantity" />
                        </div>
                        <div class="col-md-9">
                            <label for="reason" class="form-label">Reason</label>
                            <input id="reason" name="reason" class="form-control" value="{{ old('reason') }}" maxlength="255" placeholder="e.g. Pre-position for Block B-03 distribution">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-rg">Submit for approval</button>
                        <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            @endif
        </div></div>
    @endif
</x-app-layout>
