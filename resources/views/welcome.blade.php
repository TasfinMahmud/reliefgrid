<x-guest-layout>
    <h1 class="h4 mb-3">Welcome to ReliefGrid</h1>
    <p class="text-muted">
        ReliefGrid helps humanitarian teams register households, plan entitlements, manage warehouse stock,
        track shelter repairs and coordinate preparedness and incident response in settlement blocks.
    </p>
    <div class="d-grid gap-2">
        <a href="{{ route('login') }}" class="btn btn-rg">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-outline-secondary">Create a staff account</a>
    </div>
</x-guest-layout>
