<x-guest-layout>
    <h1 class="h4 mb-2">Confirm password</h1>
    <p class="small text-muted mb-3">This is a secure area of the application. Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
            <x-input-error for="password" />
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-rg">Confirm</button>
        </div>
    </form>
</x-guest-layout>
