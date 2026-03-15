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
        $north  = $request->input('north');
        $south  = $request->input('south');
        $east   = $request->input('east');
        $west   = $request->input('west');
        $zoom   = $request->input('zoom');

        $years      = $request->input('years');
        $types      = $request->input('types');
        $severity   = $request->input('severity');
        $districts  = $request->input('districts');

        $yearArr     = $years     ? explode(',', $years)     : [];
        $typeArr     = $types     ? explode(',', $types)     : [];
        $severityArr = $severity  ? explode(',', $severity)  : [];
        $districtArr = $districts ? explode(',', $districts) : [];

        // Фильтрация по полю hard_code (тяжкие - 3, средней тяжести - 2, небольшой тяжести - 1)
        $hardCodePrefixes = [];
        if (in_array('heavy',  $severityArr)) $hardCodePrefixes[] = 'тяжкие';
        if (in_array('medium', $severityArr)) $hardCodePrefixes[] = 'средней тяжести';
        if (in_array('light',  $severityArr)) $hardCodePrefixes[] = 'небольшой тяжести';

        $query = DB::table('crimes')
            ->whereBetween('latitude',  [$south, $north])
            ->whereBetween('longitude', [$west,  $east])
            ->whereBetween('latitude',  [40, 50])
            ->whereBetween('longitude', [70, 90]);

        if (!empty($yearArr)) {
            $query->whereIn('year', $yearArr);
        }

        if (!empty($typeArr)) {
            $query->where(function($q) use ($typeArr) {
                foreach ($typeArr as $type) {
                    $q->orWhere('crime_name', 'LIKE', "%{$type}%");
                }
            });
        }

        // Фильтр по hard_code вместо crime_name
        if (!empty($hardCodePrefixes)) {
            $query->where(function($q) use ($hardCodePrefixes) {
                foreach ($hardCodePrefixes as $prefix) {
                    $q->orWhere('hard_code', 'LIKE', "{$prefix}%");
                }
            });
        }

        if (!empty($districtArr)) {
            $query->where(function($q) use ($districtArr) {
                foreach ($districtArr as $d) {
                    $q->orWhere('district_name', 'LIKE', "%{$d}%");
                }
            });
        }

        if ($zoom >= 17) {
            return $query->select(
                DB::raw('latitude as lat'),
                DB::raw('longitude as lng'),
                'crime_name',
                'hard_code',   // передаём hard_code для цвета маркера на фронте
                'year'
            )->limit(1000)->get();
        }

        $grid = 0.075;

        if ($zoom > 10) $grid = 0.03;
        if ($zoom > 12) $grid = 0.015;
        if ($zoom > 14) $grid = 0.0075;
        if ($zoom > 16) $grid = 0.003;

        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        return DB::select("
            SELECT
                AVG(latitude)  as lat,
                AVG(longitude) as lng,
                COUNT(*)       as count
            FROM ({$sql}) as filtered
            GROUP BY
                FLOOR(latitude  / {$grid}),
                FLOOR(longitude / {$grid})
        ", $bindings);
    }

    public function filterOptions()
    {
        $years = DB::table('crimes')
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $districts = DB::table('crimes')
            ->select('district_name')
            ->distinct()
            ->whereNotNull('district_name')
            ->orderBy('district_name')
            ->pluck('district_name');

        return response()->json([
            'years'     => $years,
            'districts' => $districts,
        ]);
    }
}
