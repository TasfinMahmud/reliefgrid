<x-guest-layout>
    <h1 class="h4 mb-1">Create a staff account</h1>
    <p class="small text-muted mb-3">New accounts start as <strong>Field Officer</strong>. An administrator can change your role.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
            <x-input-error for="name" />
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
            <x-input-error for="email" />
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" autocomplete="tel">
            <x-input-error for="phone" />
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            <x-input-error for="password" />
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <a class="small" href="{{ route('login') }}">Already registered?</a>
            <button type="submit" class="btn btn-rg">Register</button>
        </div>
    </form>
</x-guest-layout>
