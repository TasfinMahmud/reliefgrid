@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label for="block_id" class="form-label required">Block</label>
        <select id="block_id" name="block_id" class="form-select @error('block_id') is-invalid @enderror" required>
            <option value="">Select block…</option>
            @foreach($blocks as $block)
                <option value="{{ $block->id }}" @selected(old('block_id', $household->block_id) == $block->id)>{{ $block->display_name }}</option>
            @endforeach
        </select>
        <x-input-error for="block_id" />
    </div>
    <div class="col-md-4">
        <label for="shelter_number" class="form-label">Shelter number</label>
        <input id="shelter_number" name="shelter_number" class="form-control @error('shelter_number') is-invalid @enderror" value="{{ old('shelter_number', $household->shelter_number) }}" maxlength="40">
        <x-input-error for="shelter_number" />
    </div>
    <div class="col-md-4">
        <label for="household_size" class="form-label required">Household size (members)</label>
        <input id="household_size" name="household_size" type="number" min="1" max="50" class="form-control @error('household_size') is-invalid @enderror" value="{{ old('household_size', $household->household_size ?? 1) }}" required>
        <x-input-error for="household_size" />
    </div>

    <div class="col-md-6">
        <label for="head_name" class="form-label required">Head of household</label>
        <input id="head_name" name="head_name" class="form-control @error('head_name') is-invalid @enderror" value="{{ old('head_name', $household->head_name) }}" required maxlength="120">
        <x-input-error for="head_name" />
    </div>
    <div class="col-md-2">
        <label for="head_sex" class="form-label">Sex</label>
        <select id="head_sex" name="head_sex" class="form-select">
            <option value="">—</option>
            @foreach(['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $v => $l)
                <option value="{{ $v }}" @selected(old('head_sex', $household->head_sex) === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label for="head_age" class="form-label">Age</label>
        <input id="head_age" name="head_age" type="number" min="1" max="120" class="form-control @error('head_age') is-invalid @enderror" value="{{ old('head_age', $household->head_age) }}">
        <x-input-error for="head_age" />
    </div>
    <div class="col-md-2">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $household->phone) }}" maxlength="32">
        <x-input-error for="phone" />
    </div>

    <div class="col-12">
        <label for="vulnerability_notes" class="form-label">Vulnerability notes <span class="text-muted">(keep to what is needed for assistance)</span></label>
        <textarea id="vulnerability_notes" name="vulnerability_notes" rows="2" class="form-control @error('vulnerability_notes') is-invalid @enderror">{{ old('vulnerability_notes', $household->vulnerability_notes) }}</textarea>
        <x-input-error for="vulnerability_notes" />
    </div>

    <div class="col-12">
        <div class="card bg-light border-0">
            <div class="card-body py-3">
                <h2 class="h6">Informed consent (version {{ $consentVersion }})</h2>
                <p class="small mb-2">
                    The head of household has been told, in a language they understand, that ReliefGrid stores the minimal data above
                    to plan and verify humanitarian assistance, that the token card carries no personal data, that the data is shared only
                    with partners delivering services to them, and that they may ask for their record to be deactivated at any time.
                </p>
                @if($household->consent_given)
                    <div class="small text-success">Consent recorded on {{ $household->consent_given_at?->format('d M Y H:i') }} (version {{ $household->consent_version }}).</div>
                @else
                    <div class="form-check">
                        <input class="form-check-input @error('consent_given') is-invalid @enderror" type="checkbox" id="consent_given" name="consent_given" value="1" @checked(old('consent_given'))>
                        <label class="form-check-label" for="consent_given">The head of household gave verbal/written consent as described above.</label>
                        <x-input-error for="consent_given" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
