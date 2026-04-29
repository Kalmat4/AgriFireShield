<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class YieldController extends Controller
{
    public function index(): InertiaResponse
    {
        $nationalSummary = DB::table('v_national_summary')
            ->orderByDesc('harvest_year')
            ->orderBy('crop_name')
            ->get();

        $byRegionYear = DB::table('v_yield_by_region_year')
            ->orderByDesc('harvest_year')
            ->orderBy('crop_name')
            ->orderBy('region_name')
            ->get();

        $wheatTop3 = DB::table('v_top3_wheat_dynamics')
            ->orderBy('region_name')
            ->orderByDesc('harvest_year')
            ->get();

        $latestYear = DB::table('national_yield_summary')->max('harvest_year');

        $wheatRow = DB::table('national_yield_summary')
            ->join('crops', 'crops.id', '=', 'national_yield_summary.crop_id')
            ->where('crops.code', 'wheat')
            ->where('national_yield_summary.harvest_year', $latestYear)
            ->select('national_yield_summary.gross_harvest_ton', 'national_yield_summary.export_volume_ton')
            ->first();

        $avgWheatYield = DB::table('yield_stats')
            ->join('crops', 'crops.id', '=', 'yield_stats.crop_id')
            ->where('crops.code', 'wheat')
            ->where('yield_stats.harvest_year', $latestYear)
            ->avg('yield_stats.yield_centner_ha');

        $stats = [
            'latest_year'          => $latestYear,
            'wheat_gross_harvest'  => $wheatRow?->gross_harvest_ton ?? 0,
            'wheat_export'         => $wheatRow?->export_volume_ton ?? 0,
            'avg_wheat_yield'      => round($avgWheatYield ?? 0, 1),
            'anomalies_count'      => DB::table('yield_anomalies')->count(),
        ];

        return Inertia::render('Yield', [
            'national_summary' => $nationalSummary,
            'by_region_year'   => $byRegionYear,
            'wheat_top3'       => $wheatTop3,
            'stats'            => $stats,
        ]);
    }
}
