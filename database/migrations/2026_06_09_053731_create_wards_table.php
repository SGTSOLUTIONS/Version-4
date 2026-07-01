<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function map()
    {
        $user = Auth::user();

        // Allow only Team Leader and Surveyor
        if (!in_array($user->role, ['teamleader', 'surveyor'])) {
            abort(403, 'Unauthorized access');
        }

        // Handle ward_ids (single or comma separated)
        $wardIds = explode(',', $user->ward_ids);

        $wardId = trim($wardIds[0]);

        $ward = Ward::findOrFail($wardId);

        $zone = Zone::findOrFail($ward->zone_id);

        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        // Dynamic table names
        $polygonsTableName = "polygons_{$wardId}";
        $linesTableName = "lines_{$wardId}";
        $pointsTableName = "points_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";

        $misTableName = "mis_{$corp}";
        $waterTaxTableName = "water_tax_{$corp}";

        // Check tables exist
        foreach ([
            $polygonsTableName,
            $linesTableName,
            $pointsTableName,
            $polygonDataTableName,
            $pointDataTableName,
            $misTableName,
            $waterTaxTableName
        ] as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                abort(404, "Table '{$table}' does not exist.");
            }
        }

        // GIS Data
        $polygons = DB::table($polygonsTableName)->get();
        $lines = DB::table($linesTableName)->get();
        $points = DB::table($pointsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        // MIS + Water Tax
        $misData = DB::table($misTableName . ' as mis')
            ->leftJoin(
                $waterTaxTableName . ' as wt',
                'mis.assessment',
                '=',
                'wt.assessment'
            )
            ->where('mis.ward_no', $wardNo)
            ->select(
                'mis.*',
                'wt.watertax_no',
                'wt.old_watertax_no'
            )
            ->get();

        // Road Names
        $uniqueRoadNames = DB::table($misTableName)
            ->select('road_name')
            ->whereNotNull('road_name')
            ->where('road_name', '!=', '')
            ->distinct()
            ->orderBy('road_name')
            ->pluck('road_name');

        return view('map.mapview', compact(
            'ward',
            'zone',
            'polygons',
            'points',
            'lines',
            'polygonDatas',
            'pointDatas',
            'misData',
            'uniqueRoadNames'
        ));
    }
}
