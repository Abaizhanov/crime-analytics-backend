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

        $limit = 1000;

        if ($zoom < 14) {
            $limit = 200;
        } elseif ($zoom < 16) {
            $limit = 500;
        }

        return DB::table('crimes')
            ->select('latitude', 'longitude', 'crime_name', 'year')
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east])
            ->orderByRaw('random()')
            ->limit($limit)
            ->get();
    }
}
