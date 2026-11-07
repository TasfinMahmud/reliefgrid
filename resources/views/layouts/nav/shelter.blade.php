{{-- Feature 12 – navigation entry (included from layouts/navigation.blade.php) --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('repairs.*') ? 'active' : '' }}" href="{{ route('repairs.index') }}">Shelter repairs</a>
</li>
