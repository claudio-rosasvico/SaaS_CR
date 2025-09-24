<!doctype html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Panel') — {{ config('app.name') }}</title>

    {{-- Bootstrap 5 + Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- Estilos del panel (simple, estático) --}}
    <link rel="stylesheet" href="{{ asset('panel.css') }}">

    @livewireStyles
</head>

<body>

    <div class="panel-wrap">
        {{-- Sidebar --}}
        <aside class="panel-sidebar">
            <div class="brand mb-3">
                <a class="navbar-brand fw-bold" href="{{ route('panel.dashboard') }}">
                    <i class="bi bi-chat-right-text"></i> {{ config('app.name') }}
                </a>
            </div>

            <nav class="nav flex-column">
                <a class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}"
                    href="{{ route('panel.dashboard') }}">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->is('panel/bots*') ? 'active' : '' }}" href="{{ url('/panel/bots') }}">
                    <i class="bi bi-robot"></i> Bots
                </a>
                <a class="nav-link {{ request()->routeIs('panel.sources') ? 'active' : '' }}"
                    href="{{ route('panel.sources') }}">
                    <i class="bi bi-folder2-open"></i> Fuentes
                </a>
                <a class="nav-link {{ request()->is('panel/conversations*') ? 'active' : '' }}"
                    href="{{ route('panel.conversations') }}">
                    <i class="bi bi-chat-dots"></i> Conversaciones
                </a>
                <a class="nav-link {{ request()->routeIs('panel.metrics') ? 'active' : '' }}"
                    href="{{ url('/panel/metrics') }}">
                    <i class="bi bi-graph-up"></i> Métricas
                </a>
            </nav>

            <div class="sidebar-footer mt-auto small text-muted">
                <div><i class="bi bi-building"></i> Org: {{ optional(current_org())->name ?? '—' }}</div>
                <div>v{{ \Illuminate\Foundation\Application::VERSION }}</div>
            </div>
        </aside>

        {{-- Main area --}}
        <main class="panel-main">
            {{-- Topbar --}}
            <header class="panel-topbar">
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h5 mb-0">@yield('title', 'Panel')</h1>
                    @yield('actions')
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name ?? '' }}</span>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-house"></i>
                        Inicio
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            Salir
                        </button>
                    </form>
                </div>
            </header>

            {{-- Content --}}
            <div class="panel-content">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </main>
    </div>

    {{-- Toasts (para session("ok")/("error")) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toast-area" style="z-index: 1080">
        @if (session('ok'))
            <div class="toast align-items-center text-bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check2-circle me-1"></i> {{ session('ok') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        // Auto-init toasts (los que no vengan ya con .show)
        document.querySelectorAll('.toast:not(.show)').forEach(el => new bootstrap.Toast(el).show());
    </script>
</body>

</html>
