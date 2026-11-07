<section>
    <h2 class="h5">Profile information</h2>
    <p class="small text-muted">Update your name, email address and phone number. Your role (<strong>{{ $user->roleLabel() }}</strong>) is managed by an administrator.</p>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success py-2">Saved.</div>
    @endif

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
            <x-input-error for="name" />
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error for="email" />
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
            <x-input-error for="phone" />
        </div>

        <button type="submit" class="btn btn-rg">Save</button>
    </form>
</section>
