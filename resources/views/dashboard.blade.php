<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div>
            <h1>Dashboard</h1>
            <div class="text-muted small">Welcome back, {{ auth()->user()->name }} · {{ auth()->user()->roleLabel() }}</div>
        </div>
        @if($actions->isNotEmpty())
            <div class="d-flex gap-2 flex-wrap">
                @foreach($actions as [$label, $url, $class])
                    <a href="{{ $url }}" class="btn {{ $class }} btn-sm">{{ $label }}</a>
                @endforeach
            </div>
        @endif
    </x-slot>

    @if($cards->isNotEmpty())
        <div class="row g-3 mb-4">
            @foreach($cards as [$label, $value, $url, $sub])
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ $url }}" class="text-decoration-none text-dark">
                        <div class="card stat-card h-100">
                            <div class="card-body py-3">
                                <div class="stat-label">{{ $label }}</div>
                                <div class="stat-value">{{ $value }}</div>
                                <div class="small text-muted">{{ $sub }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if($panels->isEmpty())
        <div class="card shadow-sm"><div class="card-body text-muted">
            Feature modules will appear here as they are delivered. Use <em>Setup</em> to manage blocks, items and users.
        </div></div>
    @else
        <div class="row g-4">
            @foreach($panels as $widget)
                <div class="col-lg-{{ $widget->width() }}">
                    @include($widget->view(), $widget->data())
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
