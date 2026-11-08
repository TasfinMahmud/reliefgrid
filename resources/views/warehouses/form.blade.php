<x-app-layout :title="$warehouse->exists ? 'Edit warehouse' : 'New warehouse'">
    <x-slot name="header"><h1>{{ $warehouse->exists ? 'Edit warehouse '.$warehouse->code : 'New warehouse' }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $warehouse->exists ? route('warehouses.update', $warehouse) : route('warehouses.store') }}">
            @csrf
            @if($warehouse->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="code" class="form-label required">Code</label>
                    <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $warehouse->code) }}" required maxlength="20" placeholder="WH-MAIN">
                    <x-input-error for="code" />
                </div>
                <div class="col-md-5">
                    <label for="name" class="form-label required">Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $warehouse->name) }}" required maxlength="120">
                    <x-input-error for="name" />
                </div>
                <div class="col-md-4">
                    <label for="block_id" class="form-label">Block</label>
                    <select id="block_id" name="block_id" class="form-select">
                        <option value="">—</option>
                        @foreach($blocks as $block)
                            <option value="{{ $block->id }}" @selected(old('block_id', $warehouse->block_id) == $block->id)>{{ $block->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Address / location</label>
                    <input id="address" name="address" class="form-control" value="{{ old('address', $warehouse->address) }}" maxlength="255">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $warehouse->notes) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $warehouse->is_active))>
                        <label class="form-check-label" for="is_active">Active (can receive, issue and transfer stock)</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">{{ $warehouse->exists ? 'Save changes' : 'Create warehouse' }}</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
