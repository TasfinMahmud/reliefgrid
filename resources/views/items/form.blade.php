<x-app-layout :title="$item->exists ? 'Edit item' : 'New item'">
    <x-slot name="header"><h1>{{ $item->exists ? 'Edit item '.$item->code : 'New item' }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="code" class="form-label required">Code</label>
                    <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $item->code) }}" required maxlength="30" placeholder="RICE-25">
                    <x-input-error for="code" />
                </div>
                <div class="col-md-5">
                    <label for="name" class="form-label required">Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required maxlength="120">
                    <x-input-error for="name" />
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label required">Category</label>
                    <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                        @foreach(\App\Models\Item::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $item->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="category" />
                </div>
                <div class="col-md-2">
                    <label for="unit" class="form-label required">Unit</label>
                    <input id="unit" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $item->unit) }}" required maxlength="20" placeholder="kg / pcs / litre">
                    <x-input-error for="unit" />
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="2" class="form-control">{{ old('description', $item->description) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
                        <label class="form-check-label" for="is_active">Active (available for entitlements and stock)</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">{{ $item->exists ? 'Save changes' : 'Create item' }}</button>
                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
