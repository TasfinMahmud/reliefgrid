<x-app-layout title="Report incident">
    <x-slot name="header"><h1>Report incident</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('incidents.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="block_id" class="form-label required">Block</label>
                    <select id="block_id" name="block_id" class="form-select @error('block_id') is-invalid @enderror" required>
                        <option value="">Select block…</option>
                        @foreach($blocks as $b)
                            <option value="{{ $b->id }}" @selected(old('block_id') == $b->id)>{{ $b->display_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="block_id" />
                </div>
                <div class="col-md-4">
                    <label for="hazard_type" class="form-label required">Hazard</label>
                    <select id="hazard_type" name="hazard_type" class="form-select @error('hazard_type') is-invalid @enderror" required>
                        @foreach($hazardTypes as $v => $l)
                            <option value="{{ $v }}" @selected(old('hazard_type') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="hazard_type" />
                </div>
                <div class="col-md-4">
                    <label for="occurred_at" class="form-label required">Occurred at</label>
                    <input id="occurred_at" name="occurred_at" type="datetime-local" class="form-control @error('occurred_at') is-invalid @enderror" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
                    <x-input-error for="occurred_at" />
                </div>
                <div class="col-md-8">
                    <label for="title" class="form-label required">Title</label>
                    <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="150">
                    <x-input-error for="title" />
                </div>
                <div class="col-md-4">
                    <label for="hazard_alert_id" class="form-label">Linked alert</label>
                    <select id="hazard_alert_id" name="hazard_alert_id" class="form-select">
                        <option value="">— none —</option>
                        @foreach($alerts as $a)
                            <option value="{{ $a->id }}" @selected(old('hazard_alert_id', $selectedAlert) == $a->id)>{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label required">What happened?</label>
                    <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" required minlength="10">{{ old('description') }}</textarea>
                    <x-input-error for="description" />
                </div>
                <div class="col-md-3">
                    <label for="households_affected" class="form-label">Households affected (est.)</label>
                    <input id="households_affected" name="households_affected" type="number" min="0" class="form-control" value="{{ old('households_affected') }}">
                </div>
                <div class="col-md-3">
                    <label for="people_affected" class="form-label">People affected (est.)</label>
                    <input id="people_affected" name="people_affected" type="number" min="0" class="form-control" value="{{ old('people_affected') }}">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">Report incident</button>
                <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
