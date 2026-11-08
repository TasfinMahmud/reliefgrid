{{-- Feature 2 – navigation entry (included from layouts/navigation.blade.php) --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('cycles.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}">Assistance cycles</a>
</li>
