<x-app-layout :title="$block->exists ? 'Edit block' : 'New block'">
    <x-slot name="header"><h1>{{ $block->exists ? 'Edit block '.$block->code : 'New block' }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $block->exists ? route('blocks.update', $block) : route('blocks.store') }}">
            @csrf
            @if($block->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="code" class="form-label required">Code</label>
                    <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $block->code) }}" required maxlength="20" placeholder="B-01">
                    <x-input-error for="code" />
                </div>
                <div class="col-md-5">
                    <label for="name" class="form-label required">Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $block->name) }}" required maxlength="100">
                    <x-input-error for="name" />
                </div>
                <div class="col-md-2">
                    <label for="camp" class="form-label">Camp</label>
                    <input id="camp" name="camp" class="form-control" value="{{ old('camp', $block->camp) }}" maxlength="100">
                </div>
                <div class="col-md-2">
                    <label for="sector" class="form-label">Sector</label>
                    <input id="sector" name="sector" class="form-control" value="{{ old('sector', $block->sector) }}" maxlength="50">
                </div>
                <div class="col-md-3">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="0.0000001" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude', $block->latitude) }}">
                    <x-input-error for="latitude" />
                </div>
                <div class="col-md-3">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="0.0000001" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude', $block->longitude) }}">
                    <x-input-error for="longitude" />
                </div>
                <div class="col-md-3">
                    <label for="estimated_population" class="form-label">Estimated population</label>
                    <input id="estimated_population" name="estimated_population" type="number" min="0" class="form-control @error('estimated_population') is-invalid @enderror" value="{{ old('estimated_population', $block->estimated_population) }}">
                    <x-input-error for="estimated_population" />
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $block->notes) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">{{ $block->exists ? 'Save changes' : 'Create block' }}</button>
                <a href="{{ route('blocks.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
