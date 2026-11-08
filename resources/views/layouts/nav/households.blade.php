{{-- Feature 1 – navigation entry (included from layouts/navigation.blade.php) --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('households.*', 'tokens.*') ? 'active' : '' }}" href="{{ route('households.index') }}">Households</a>
</li>
