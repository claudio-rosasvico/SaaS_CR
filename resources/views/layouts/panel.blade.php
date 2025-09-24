<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Panel' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
</head>

<body>
    <nav class="navbar navbar-expand bg-white px-3 sticky-top">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('panel.dashboard') }}">
            @php $logo = config('app.logo_url') ?? asset('images/logo-crdw.svg'); @endphp
            <img src="{{ $logo }}" alt="Logo">
            <strong>{{ config('app.name') }}</strong>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-inline">Org: {{ optional(current_org())->name ?? '—' }}</span>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.dashboard') }}">Mi cuenta</a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf
                <button class="btn btn-sm btn-primary">Salir</button>
            </form>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <aside class="col-12 col-md-3 col-lg-2 cb-sidebar p-3">
                @php
                    $nav = [
                        ['Panel', route('panel.dashboard'), request()->routeIs('panel.dashboard')],
                        ['Fuentes', route('panel.sources'), request()->routeIs('panel.sources')],
                        ['Bots', route('panel.bots'), request()->routeIs('panel.bots')],
                        ['Conversaciones', route('panel.conversations'), request()->routeIs('panel.conversations*')],
                        ['Métricas', route('panel.metrics'), request()->routeIs('panel.metrics')],
                    ];
                @endphp
                <div class="vstack gap-1">
                    @foreach ($nav as [$label, $href, $active])
                        <a href="{{ $href }}" class="{{ $active ? 'active' : '' }}">
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </aside>

            <main class="col-12 col-md-9 col-lg-10 p-3 p-md-4">
                @isset($header)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">{{ $header }}</h4>
                        {{ $actions ?? '' }}
                    </div>
                @endisset

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
