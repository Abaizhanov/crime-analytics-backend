<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrimeController extends Controller
{
    public function statsByYear()
    {
        return DB::table('crimes')
            ->select('year', DB::raw('COUNT(*) as total'))
            ->groupBy('year')
            ->orderBy('year')
            ->get();
    }

    public function map(Request $request)
    {
        $north = $request->input('north');
        $south = $request->input('south');
        $east = $request->input('east');
        $west = $request->input('west');
        $zoom = $request->input('zoom');

        if ($zoom >= 17) {

            return DB::table('crimes')
                ->select(
                    DB::raw('latitude as lat'),
                    DB::raw('longitude as lng'),
                    'crime_name',
                    'year'
                )
                ->whereBetween('latitude', [$south, $north])
                ->whereBetween('longitude', [$west, $east])
                ->whereBetween('latitude', [40, 50])
                ->whereBetween('longitude', [70, 90])
                ->limit(500)
                ->get();
        }

        $grid = 0.075;

        if ($zoom > 10) $grid = 0.03;
        if ($zoom > 12) $grid = 0.015;
        if ($zoom > 14) $grid = 0.0075;
        if ($zoom > 16) $grid = 0.003;

        return DB::select("
SELECT
    AVG(latitude) as lat,
    AVG(longitude) as lng,
    COUNT(*) as count
FROM crimes
WHERE latitude BETWEEN ? AND ?
AND longitude BETWEEN ? AND ?
GROUP BY
    FLOOR(latitude / ?) ,
    FLOOR(longitude / ?)
", [
            $south, $north,
            $west, $east,
            $grid,
            $grid
        ]);
    }
}
