<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') . ' • Panel' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/panel') }}">{{ config('app.name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPanel">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div id="navPanel" class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ url('/panel/sources') }}">Fuentes</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/panel/conversations') }}">Conversaciones</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>
