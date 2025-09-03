@extends('layouts.app')

@section('content')
    {{-- Cabecera del panel (opcional) --}}
    <div class="mb-3">
        <h1 class="h4">{{ $panelTitle ?? 'Panel' }}</h1>
    </div>

    {{-- Zona de contenido del panel --}}
    @yield('panel-content')
@endsection
