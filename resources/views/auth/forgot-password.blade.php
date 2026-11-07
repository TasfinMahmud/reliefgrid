<x-guest-layout>
    <h1 class="h4 mb-2">Forgot your password?</h1>
    <p class="small text-muted mb-3">Enter your email address and we will email you a password reset link.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
            <x-input-error for="email" />
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <a class="small" href="{{ route('login') }}">Back to login</a>
            <button type="submit" class="btn btn-rg">Email reset link</button>
        </div>
    </form>
</x-guest-layout>
