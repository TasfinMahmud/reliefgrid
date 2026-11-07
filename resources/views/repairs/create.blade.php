<x-app-layout title="New repair request">
    <x-slot name="header"><h1 class="h3 mb-0">New shelter repair request</h1></x-slot>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('repairs.store') }}" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="household_id" class="form-label">Household <span class="text-danger">*</span></label>
                        <select id="household_id" name="household_id" class="form-select @error('household_id') is-invalid @enderror" required>
                            <option value="">Select household…</option>
                            @foreach($households as $h)
                                <option value="{{ $h->id }}" @selected((string) old('household_id', $selectedHousehold) === (string) $h->id)>
                                    {{ $h->code }} · {{ $h->head_name }} · {{ $h->block?->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('household_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="damage_type" class="form-label">Type of damage <span class="text-danger">*</span></label>
                        <select id="damage_type" name="damage_type" class="form-select @error('damage_type') is-invalid @enderror" required>
                            @foreach($damageTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('damage_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('damage_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">What is damaged? <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="4" maxlength="2000"
                                  class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">Submit request</button>
                    <a href="{{ route('repairs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
