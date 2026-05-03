<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EcologyController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Ecology', [
            'eco_stats' => [
                'degraded_area_mln_ha'   => 14.2,
                'water_stress_regions'   => 8,
                'co2_from_fires_kton'    => 342,
                'soil_risk_high'         => 5,
            ],
            'regions_eco' => [
                ['region' => 'Костанайская',          'degradation_index' => 3.2, 'water_stress' => 'высокий',     'co2_kton' => 45],
                ['region' => 'Акмолинская',            'degradation_index' => 2.8, 'water_stress' => 'средний',     'co2_kton' => 38],
                ['region' => 'Павлодарская',           'degradation_index' => 4.1, 'water_stress' => 'высокий',     'co2_kton' => 52],
                ['region' => 'Карагандинская',         'degradation_index' => 3.7, 'water_stress' => 'критический', 'co2_kton' => 61],
                ['region' => 'Северо-Казахстанская',   'degradation_index' => 2.1, 'water_stress' => 'низкий',      'co2_kton' => 28],
                ['region' => 'Западно-Казахстанская',  'degradation_index' => 3.9, 'water_stress' => 'высокий',     'co2_kton' => 44],
            ],
        ]);
    }
}
