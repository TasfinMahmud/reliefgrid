@php($user = auth()->user())
<nav class="navbar navbar-expand-lg navbar-rg navbar-dark shadow-sm no-print">
    <div class="container-xxl">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">ReliefGrid</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('households.*', 'tokens.*') ? 'active' : '' }}" href="{{ route('households.index') }}">Households</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('cycles.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}">Assistance cycles</a>
                </li>
                @if($user->isManager())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('blocks.*', 'admin.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Setup</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('blocks.index') }}">Blocks</a></li>
                            <li><a class="dropdown-item" href="{{ route('items.index') }}">Item catalogue</a></li>
                            @if($user->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">Users &amp; roles</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
            </ul>

            <form class="d-flex me-lg-3 mb-2 mb-lg-0" method="POST" action="{{ route('tokens.lookup') }}" role="search">
                @csrf
                <input class="form-control form-control-sm" type="search" name="token_uid" placeholder="Token UID lookup" aria-label="Token UID" required>
            </form>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ $user->name }} <span class="badge bg-light text-dark ms-1">{{ $user->roleLabel() }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Log out</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
