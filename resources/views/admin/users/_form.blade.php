@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label required">Full name</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
        <x-input-error for="name" />
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label required">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
        <x-input-error for="email" />
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
        <x-input-error for="phone" />
    </div>
    <div class="col-md-6">
        <label for="role" class="form-label required">Role</label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <x-input-error for="role" />
        <div class="form-text">
            @foreach($roles as $role)
                <div><strong>{{ $role->label() }}:</strong> {{ $role->description() }}</div>
            @endforeach
        </div>
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label {{ $user->exists ? '' : 'required' }}">{{ $user->exists ? 'New password (leave blank to keep)' : 'Password' }}</label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ $user->exists ? '' : 'required' }} autocomplete="new-password">
        <x-input-error for="password" />
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
    </div>
</div>
<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-rg">{{ $user->exists ? 'Save changes' : 'Create user' }}</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
