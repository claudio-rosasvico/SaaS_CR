// resources/views/panel/billing.blade.php
@extends('layouts.panel')

@section('panel-content')
    <h5 class="mb-3">Costos estimados — {{ $month }}</h5>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Turnos</th>
                        <th>Tokens IN</th>
                        <th>Tokens OUT</th>
                        <th>Costo (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="text-capitalize">{{ $r['provider'] }}</td>
                            <td>{{ number_format($r['turnos']) }}</td>
                            <td>{{ number_format($r['in']) }}</td>
                            <td>{{ number_format($r['out']) }}</td>
                            <td>${{ number_format($r['cost'], 4) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">Sin datos este mes.</td>
                        </tr>
                    @endforelse
                    <tr class="table-light">
                        <th colspan="4" class="text-end">Total</th>
                        <th>${{ number_format($total, 4) }}</th>
                    </tr>
                </tbody>
            </table>
            <div class="small text-muted">Nota: tokens aproximados en Ollama (chars/4).</div>
        </div>
    </div>
@endsection
