<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index()
    {
        $org = current_org_id();
        $monthStart = now()->startOfMonth();

        $agg = DB::table('analytics_events')
            ->select('provider',
                DB::raw('SUM(tokens_in) as tin'),
                DB::raw('SUM(tokens_out) as tout'),
                DB::raw('COUNT(*) as cnt'))
            ->where('organization_id', $org)
            ->where('created_at', '>=', $monthStart)
            ->groupBy('provider')
            ->get();

        $rows = [];
        $total = 0;
        foreach ($agg as $r) {
            $inK  = ($r->tin ?? 0) / 1000;
            $outK = ($r->tout ?? 0) / 1000;

            $cin  = match($r->provider) {
                'openai' => (float)env('BILL_OPENAI_IN', 0.0),
                'gemini' => (float)env('BILL_GEMINI_IN', 0.0),
                'ollama' => (float)env('BILL_OLLAMA_IN', 0.0),
                default  => 0.0,
            };
            $cout = match($r->provider) {
                'openai' => (float)env('BILL_OPENAI_OUT', 0.0),
                'gemini' => (float)env('BILL_GEMINI_OUT', 0.0),
                'ollama' => (float)env('BILL_OLLAMA_OUT', 0.0),
                default  => 0.0,
            };

            $cost = $inK * $cin + $outK * $cout;
            $total += $cost;

            $rows[] = [
                'provider' => $r->provider,
                'turnos'   => $r->cnt,
                'in'       => (int)$r->tin,
                'out'      => (int)$r->tout,
                'cost'     => $cost,
            ];
        }

        return view('panel.billing', [
            'rows'  => $rows,
            'total' => $total,
            'month' => $monthStart->format('F Y'),
        ]);
    }
}
