<x-app-layout :title="$cycle->exists ? 'Edit cycle' : 'New cycle'">
    <x-slot name="header"><h1>{{ $cycle->exists ? 'Edit cycle' : 'New assistance cycle' }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $cycle->exists ? route('cycles.update', $cycle) : route('cycles.store') }}">
            @csrf
            @if($cycle->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label required">Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cycle->name) }}" required maxlength="120" placeholder="e.g. November 2026 general food distribution">
                    <x-input-error for="name" />
                </div>
                <div class="col-md-3">
                    <label for="starts_on" class="form-label required">Starts on</label>
                    <input id="starts_on" name="starts_on" type="date" class="form-control @error('starts_on') is-invalid @enderror" value="{{ old('starts_on', $cycle->starts_on?->toDateString()) }}" required>
                    <x-input-error for="starts_on" />
                </div>
                <div class="col-md-3">
                    <label for="ends_on" class="form-label required">Ends on</label>
                    <input id="ends_on" name="ends_on" type="date" class="form-control @error('ends_on') is-invalid @enderror" value="{{ old('ends_on', $cycle->ends_on?->toDateString()) }}" required>
                    <x-input-error for="ends_on" />
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="2" class="form-control">{{ old('description', $cycle->description) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Block scope <span class="text-muted">(leave all unchecked to include every block)</span></label>
                    <div class="row">
                        @foreach($blocks as $block)
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="block_ids[]" id="block_{{ $block->id }}" value="{{ $block->id }}" @checked(in_array($block->id, old('block_ids', $selected)))>
                                    <label class="form-check-label" for="block_{{ $block->id }}">{{ $block->display_name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <x-input-error for="block_ids" />
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">{{ $cycle->exists ? 'Save changes' : 'Create draft cycle' }}</button>
                <a href="{{ $cycle->exists ? route('cycles.show', $cycle) : route('cycles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
