<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ward;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function map()
    {


        $user = Auth::user();

        if (!in_array($user->role, ['teamleader', 'surveyor'])) {
            abort(403, 'Unauthorized access');
        }

        if ($user->isTeamLeader()) {

            // Team Leader's own ward
            $wardId = $user->ward_id;
        } elseif ($user->isSurveyor()) {

            // Surveyor has no Team Leader assigned
            if (!$user->teamLeader) {
                return back()->with('error', 'No Team Leader assigned.');
            }

            // Team Leader has no ward assigned
            if (empty($user->teamLeader->ward_id)) {
                return back()->with('error', 'Your Team Leader has no Ward assigned.');
            }

            // Use Team Leader's ward
            $wardId = $user->teamLeader->ward_id;
        }

        $ward = Ward::findOrFail($wardId);

        $ward = Ward::findOrFail($wardId);

        $zoneId = $ward->zone_id;
        $zone = Zone::findOrFail($zoneId);

        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;
        $polygonsTableName = "polygons_{$wardId}";
        $linesTableName = "lines_{$wardId}";
        $pointsTableName = "points_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";

        $misTableName = "mis_{$corp}";
        $waterTaxTableName = "water_tax_{$corp}";
        $ugdtable = "ugd_tax_{$corp}";
        $prefessionaltax = "professional_tax_{$corp}";

        // GIS data
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
            ->leftJoin(
                $ugdtable . ' as ugd',
                'mis.assessment',
                '=',
                'ugd.assessment'
            )
            ->leftJoin(
                $prefessionaltax . ' as pt',
                'mis.assessment',
                '=',
                'pt.assessment'
            )
            ->where('mis.ward_no', $wardNo)
            ->select(
                'mis.*',

                // Water Tax
                'wt.watertax_no',
                'wt.old_watertax_no',

                // UGD Tax
                'ugd.ugd_no',
                'ugd.old_ugd_no',

                // Professional Tax
                'pt.pt_number',
                'pt.old_pt_number'
            )
            ->get();

        // Unique Road Names
        $uniqueRoadNames = DB::table($misTableName)
            ->select('road_name')
            ->whereNotNull('road_name')
            ->where('road_name', '!=', '')
            ->distinct()
            ->orderBy('road_name')
            ->pluck('road_name');

        return view('map.mapview', compact(
            'ward',
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
