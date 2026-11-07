{{-- Feature 14 – navigation entry (included from layouts/navigation.blade.php) --}}
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('alerts.*', 'sites.*', 'incidents.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Preparedness</a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('alerts.index') }}">Hazard alerts</a></li>
        <li><a class="dropdown-item" href="{{ route('sites.index') }}">Evacuation sites</a></li>
        <li><a class="dropdown-item" href="{{ route('incidents.index') }}">Incidents</a></li>
    </ul>
</li>
