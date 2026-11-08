{{-- Feature 5 – navigation entry (included from layouts/navigation.blade.php) --}}
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('warehouses.*', 'stock.*', 'transfers.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Warehouse</a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('warehouses.index') }}">Warehouses &amp; stock</a></li>
        <li><a class="dropdown-item" href="{{ route('transfers.index') }}">Transfers</a></li>
        <li><a class="dropdown-item" href="{{ route('stock.ledger') }}">Movement ledger</a></li>
        <li><a class="dropdown-item" href="{{ route('stock.expiry') }}">Expiry alerts</a></li>
    </ul>
</li>
