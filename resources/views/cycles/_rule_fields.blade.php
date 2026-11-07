@php($isNew = $rule === null)
<div class="row g-3">
    <div class="col-12">
        <label for="{{ $prefix }}_item_id" class="form-label required">Item</label>
        <select id="{{ $prefix }}_item_id" name="item_id" class="form-select" required>
            <option value="">Select item…</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" @selected(($isNew ? old('item_id') : $rule->item_id) == $item->id)>{{ $item->name }} ({{ $item->unit }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="{{ $prefix }}_min" class="form-label required">Min household size</label>
        <input id="{{ $prefix }}_min" name="min_household_size" type="number" min="1" max="50" class="form-control" value="{{ $isNew ? old('min_household_size', 1) : $rule->min_household_size }}" required>
    </div>
    <div class="col-6">
        <label for="{{ $prefix }}_max" class="form-label">Max household size <span class="text-muted">(blank = no limit)</span></label>
        <input id="{{ $prefix }}_max" name="max_household_size" type="number" min="1" max="50" class="form-control" value="{{ $isNew ? old('max_household_size') : $rule->max_household_size }}">
    </div>
    <div class="col-4">
        <label for="{{ $prefix }}_base" class="form-label required">Base quantity</label>
        <input id="{{ $prefix }}_base" name="base_quantity" type="number" step="0.01" min="0" class="form-control" value="{{ $isNew ? old('base_quantity', 0) : $rule->base_quantity }}" required>
    </div>
    <div class="col-4">
        <label for="{{ $prefix }}_pm" class="form-label required">Per member</label>
        <input id="{{ $prefix }}_pm" name="per_member_quantity" type="number" step="0.01" min="0" class="form-control" value="{{ $isNew ? old('per_member_quantity', 0) : $rule->per_member_quantity }}" required>
    </div>
    <div class="col-4">
        <label for="{{ $prefix }}_cap" class="form-label">Max quantity</label>
        <input id="{{ $prefix }}_cap" name="max_quantity" type="number" step="0.01" min="0.01" class="form-control" value="{{ $isNew ? old('max_quantity') : $rule->max_quantity }}">
    </div>
    <div class="col-12">
        <label for="{{ $prefix }}_notes" class="form-label">Notes</label>
        <input id="{{ $prefix }}_notes" name="notes" class="form-control" maxlength="255" value="{{ $isNew ? old('notes') : $rule->notes }}">
    </div>
</div>
