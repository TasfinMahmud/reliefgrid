<x-app-layout :title="$site->exists ? 'Edit site' : 'New site'">
    <x-slot name="header"><h1>{{ $site->exists ? 'Edit evacuation site '.$site->code : 'New evacuation site' }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $site->exists ? route('sites.update', $site) : route('sites.store') }}">
            @csrf
            @if($site->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="code" class="form-label required">Code</label>
                    <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $site->code) }}" required maxlength="20" placeholder="EV-01">
                    <x-input-error for="code" />
                </div>
                <div class="col-md-5">
                    <label for="name" class="form-label required">Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $site->name) }}" required maxlength="120">
                    <x-input-error for="name" />
                </div>
                <div class="col-md-4">
                    <label for="block_id" class="form-label required">Block</label>
                    <select id="block_id" name="block_id" class="form-select @error('block_id') is-invalid @enderror" required>
                        <option value="">Select block…</option>
                        @foreach($blocks as $block)
                            <option value="{{ $block->id }}" @selected(old('block_id', $site->block_id) == $block->id)>{{ $block->display_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="block_id" />
                </div>
                <div class="col-md-3">
                    <label for="capacity" class="form-label required">Capacity (people)</label>
                    <input id="capacity" name="capacity" type="number" min="1" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $site->capacity) }}" required>
                    <x-input-error for="capacity" />
                </div>
                <div class="col-md-3">
                    <label for="contact_person" class="form-label">Contact person</label>
                    <input id="contact_person" name="contact_person" class="form-control" value="{{ old('contact_person', $site->contact_person) }}" maxlength="120">
                </div>
                <div class="col-md-3">
                    <label for="contact_phone" class="form-label">Contact phone</label>
                    <input id="contact_phone" name="contact_phone" class="form-control" value="{{ old('contact_phone', $site->contact_phone) }}" maxlength="32">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Coordinates</label>
                    <div class="input-group">
                        <input name="latitude" type="number" step="0.0000001" class="form-control" placeholder="lat" value="{{ old('latitude', $site->latitude) }}">
                        <input name="longitude" type="number" step="0.0000001" class="form-control" placeholder="lng" value="{{ old('longitude', $site->longitude) }}">
                    </div>
                    <x-input-error for="latitude" /><x-input-error for="longitude" />
                </div>
                <div class="col-12">
                    <label for="facilities" class="form-label">Facilities (water, latrines, lighting…)</label>
                    <textarea id="facilities" name="facilities" rows="2" class="form-control">{{ old('facilities', $site->facilities) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">{{ $site->exists ? 'Save changes' : 'Create site' }}</button>
                <a href="{{ route('sites.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
