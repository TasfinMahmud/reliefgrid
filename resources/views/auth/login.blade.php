<x-guest-layout>
    <h1 class="h4 mb-3">Log in</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            <x-input-error for="email" />
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
            <x-input-error for="password" />
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label class="form-check-label" for="remember_me">Remember me</label>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
            <button type="submit" class="btn btn-rg">Log in</button>
        </div>
    </form>

    <hr>
    <p class="small text-muted mb-0">No account yet? <a href="{{ route('register') }}">Register</a></p>
</x-guest-layout>
