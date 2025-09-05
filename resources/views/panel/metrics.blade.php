@extends('layouts.panel')

@section('panel-content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Latencia promedio (7d)</div>
                    <div class="h4 mb-0">{{ $avgLatency }} ms</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Tokens IN (7d)</div>
                    <div class="h4 mb-0">{{ number_format($tokIn) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Tokens OUT (7d)</div>
                    <div class="h4 mb-0">{{ number_format($tokOut) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Eventos (14d)</div>
                    <div class="h4 mb-0">{{ array_sum($dailyCounts) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Turnos por día (14 días)</h5>
            <canvas id="chartDaily" height="90"></canvas>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Uso por proveedor (7 días)</h5>
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Eventos</th>
                        <th>Latencia prom.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byProvider as $row)
                        <tr>
                            <td class="text-capitalize">{{ $row->provider ?? '—' }}</td>
                            <td>{{ $row->c }}</td>
                            <td>{{ (int) $row->ms }} ms</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">Sin datos aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($dailyLabels);
        const data = @json($dailyCounts);

        new Chart(document.getElementById('chartDaily'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Turnos',
                    data,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
@endpush
