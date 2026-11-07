<x-guest-layout>
    <h1 class="h4 mb-3">Reset password</h1>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            <x-input-error for="email" />
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            <x-input-error for="password" />
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-rg">Reset password</button>
        </div>
    </form>
</x-guest-layout>
