<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SubsidyController extends Controller
{
    public function index(): InertiaResponse
    {
        $subsidyFlags = DB::table('v_subsidy_flags')
            ->orderByDesc('deviation_pct')
            ->get();

        $anomalies = DB::table('v_anomalies_detail')
            ->where('resolved', 0)
            ->get();

        $stats = [
            'total_subsidy_kzt'       => DB::table('subsidy_data')->sum('subsidy_amount_kzt'),
            'total_potential_loss_kzt' => DB::table('v_subsidy_flags')->sum('potential_loss_kzt'),
            'critical_count'           => DB::table('yield_anomalies')->where('severity', 'critical')->count(),
            'fraud_count'              => DB::table('yield_anomalies')->where('anomaly_type', 'subsidy_fraud')->count(),
        ];

        return Inertia::render('Subsidies', [
            'subsidy_flags' => $subsidyFlags,
            'anomalies'     => $anomalies,
            'stats'         => $stats,
        ]);
    }
}
