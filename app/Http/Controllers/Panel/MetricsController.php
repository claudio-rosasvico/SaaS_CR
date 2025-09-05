<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function index()
    {
        $org = current_org_id();

        // Turnos por día (últimos 14)
        $daily = DB::table('analytics_events')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('organization_id', $org)
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('d')->orderBy('d')
            ->get();

        // Latencia promedio (ms) últimos 7 días
        $avgLatency = DB::table('analytics_events')
            ->where('organization_id', $org)
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('duration_ms');

        // Uso por proveedor últimos 7 días
        $byProvider = DB::table('analytics_events')
            ->select('provider', DB::raw('COUNT(*) as c'), DB::raw('AVG(duration_ms) as ms'))
            ->where('organization_id', $org)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('provider')
            ->orderByDesc('c')
            ->get();

        // Tokens aprox últimos 7 días
        $tokens = DB::table('analytics_events')
            ->selectRaw('SUM(tokens_in) as in_sum, SUM(tokens_out) as out_sum')
            ->where('organization_id', $org)
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        return view('panel.metrics', [
            'dailyLabels'  => $daily->pluck('d')->map(fn($d)=> (string)$d)->all(),
            'dailyCounts'  => $daily->pluck('c')->map(fn($x)=>(int)$x)->all(),
            'avgLatency'   => round($avgLatency ?? 0),
            'byProvider'   => $byProvider,
            'tokIn'        => (int) ($tokens->in_sum ?? 0),
            'tokOut'       => (int) ($tokens->out_sum ?? 0),
        ]);
    }
}
