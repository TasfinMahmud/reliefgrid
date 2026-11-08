<x-app-layout :title="'Receive stock · '.$warehouse->code">
    <x-slot name="header"><h1>Receive stock into {{ $warehouse->code }} · {{ $warehouse->name }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('stock.receive', $warehouse) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label required">Item</label>
                    <select id="item_id" name="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                        <option value="">Select item…</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name }} ({{ $item->unit }}) · {{ $item->categoryLabel() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="item_id" />
                </div>
                <div class="col-md-3">
                    <label for="batch_number" class="form-label required">Batch / lot number</label>
                    <input id="batch_number" name="batch_number" class="form-control @error('batch_number') is-invalid @enderror" value="{{ old('batch_number') }}" required maxlength="60">
                    <x-input-error for="batch_number" />
                </div>
                <div class="col-md-3">
                    <label for="quantity" class="form-label required">Quantity</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required>
                    <x-input-error for="quantity" />
                </div>
                <div class="col-md-3">
                    <label for="received_at" class="form-label required">Received on</label>
                    <input id="received_at" name="received_at" type="date" class="form-control @error('received_at') is-invalid @enderror" value="{{ old('received_at', now()->toDateString()) }}" required>
                    <x-input-error for="received_at" />
                </div>
                <div class="col-md-3">
                    <label for="expiry_date" class="form-label">Expiry date</label>
                    <input id="expiry_date" name="expiry_date" type="date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}">
                    <x-input-error for="expiry_date" />
                </div>
                <div class="col-md-6">
                    <label for="supplier" class="form-label">Supplier / donor</label>
                    <input id="supplier" name="supplier" class="form-control" value="{{ old('supplier') }}" maxlength="120">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes (waybill number, condition on arrival…)</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">Record receipt</button>
                <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
