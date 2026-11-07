<x-app-layout title="Issue alert">
    <x-slot name="header"><h1>Issue hazard alert</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('alerts.store') }}">
            @csrf
            <div class="row g-3">
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
                    <label for="severity" class="form-label required">Severity</label>
                    <select id="severity" name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                        @foreach($severities as $s)
                            <option value="{{ $s->value }}" @selected(old('severity', 'warning') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="severity" />
                </div>
                <div class="col-md-4">
                    <label for="expires_at" class="form-label">Expires at <span class="text-muted">(blank = until cancelled)</span></label>
                    <input id="expires_at" name="expires_at" type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">
                    <x-input-error for="expires_at" />
                </div>
                <div class="col-12">
                    <label for="title" class="form-label required">Title</label>
                    <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="150" placeholder="e.g. Heavy rainfall – landslide risk on slopes">
                    <x-input-error for="title" />
                </div>
                <div class="col-12">
                    <label for="message" class="form-label required">Message</label>
                    <textarea id="message" name="message" rows="3" class="form-control @error('message') is-invalid @enderror" required minlength="10">{{ old('message') }}</textarea>
                    <x-input-error for="message" />
                </div>
                <div class="col-12">
                    <label for="instructions" class="form-label">Instructions for residents / staff</label>
                    <textarea id="instructions" name="instructions" rows="2" class="form-control">{{ old('instructions') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label required">Blocks</label>
                    <div class="row">
                        @foreach($blocks as $block)
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="block_ids[]" id="block_{{ $block->id }}" value="{{ $block->id }}" @checked(in_array($block->id, old('block_ids', [])))>
                                    <label class="form-check-label" for="block_{{ $block->id }}">{{ $block->display_name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <x-input-error for="block_ids" />
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-danger">Issue alert</button>
                <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
