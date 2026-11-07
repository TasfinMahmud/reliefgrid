<x-guest-layout>
    <h1 class="h4 mb-2">Verify your email</h1>
    <p class="small text-muted mb-3">Thanks for signing up! Please verify your email address by clicking the link we just emailed you. If you did not receive the email, we will gladly send another.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">A new verification link has been sent to the email address you provided during registration.</div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-rg">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link">Log out</button>
        </form>
    </div>
</x-guest-layout>
