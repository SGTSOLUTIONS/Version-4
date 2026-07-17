<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\Zone;
use App\Models\Ward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionerController extends Controller
{
    /**
     * Get accessible ward IDs based on user role
     */
    private function getAccessibleWardIds()
    {
        $user = Auth::user();
        $wardIds = [];

        if ($user->isCommissioner() || $user->isDC()) {
            // Commissioner and DC have access to all wards in their corporation
            $corporation = Corporation::find($user->corporation_id);
            if ($corporation) {
                $zones = Zone::where('corp_id', $corporation->id)->get();
                foreach ($zones as $zone) {
                    $wards = Ward::where('zone_id', $zone->id)->get();
                    foreach ($wards as $ward) {
                        $wardIds[] = $ward->id;
                    }
                }
            }
        } elseif ($user->isAC() || $user->isARO()) {
            // AC and ARO have access to wards in their assigned zone only
            if ($user->zone_id) {
                $wards = Ward::where('zone_id', $user->zone_id)->get();
                foreach ($wards as $ward) {
                    $wardIds[] = $ward->id;
                }
            }
        } elseif ($user->isBC()) {
            // BC has access to only their assigned ward
            if ($user->ward_id) {
                $wardIds[] = $user->ward_id;
            }
        }

        return $wardIds;
    }

    /**
     * Get accessible ward IDs with ward numbers for a specific corporation
     */
    private function getAccessibleWardNos($corporationId)
    {
        $user = Auth::user();
        $wardNos = [];

        if ($user->isCommissioner() || $user->isDC()) {
            // All wards in corporation
            $zones = Zone::where('corp_id', $corporationId)->get();
            foreach ($zones as $zone) {
                $wards = Ward::where('zone_id', $zone->id)->get();
                foreach ($wards as $ward) {
                    $wardNos[] = $ward->ward_no;
                }
            }
        } elseif ($user->isAC() || $user->isARO()) {
            // Wards in assigned zone
            if ($user->zone_id) {
                $wards = Ward::where('zone_id', $user->zone_id)->get();
                foreach ($wards as $ward) {
                    $wardNos[] = $ward->ward_no;
                }
            }
        } elseif ($user->isBC()) {
            // Only assigned ward
            if ($user->ward_id) {
                $ward = Ward::find($user->ward_id);
                if ($ward) {
                    $wardNos[] = $ward->ward_no;
                }
            }
        }

        return $wardNos;
    }

    public function dashboard()
    {
        $user = auth()->user();
        $accessibleWardIds = $this->getAccessibleWardIds();
        $corporation = Corporation::with(['zones.wards'])->find($user->corporation_id);

        if (!$corporation || empty($accessibleWardIds)) {
            return view('main.Commissioner.dashboard', [
                'error' => 'No accessible wards found for your role. Please contact administrator.',
                'stats' => $this->getEmptyStats(),
                'zoneData' => collect(),
                'wardData' => collect(),
                'buildingData' => collect(),
                'assessmentData' => collect(),
                'performanceZones' => collect(),
                'activities' => collect(),
                'hierarchyStats' => $this->getEmptyHierarchyStats(),
                'corporation' => null,
                'user' => $user,
                'taxBreakdown' => $this->getEmptyTaxBreakdown(),
                'getAllwardBoundary' => [],
                'waterTaxData' => [],
                'ugdData' => [],
                'professionalTaxData' => [],
                'wardVariationStats' => collect(),
            ]);
        }

        // Get accessible zones based on role
        $zones = collect();
        if ($user->isCommissioner() || $user->isDC()) {
            $zones = $corporation->zones()->with(['wards'])->get();
        } elseif ($user->isAC() || $user->isARO()) {
            if ($user->zone_id) {
                $zone = Zone::with(['wards'])->find($user->zone_id);
                if ($zone) {
                    $zones = collect([$zone]);
                }
            }
        } elseif ($user->isBC()) {
            if ($user->ward_id) {
                $ward = Ward::with(['zone'])->find($user->ward_id);
                if ($ward && $ward->zone) {
                    $zone = Zone::with(['wards' => function($query) use ($user) {
                        $query->where('id', $user->ward_id);
                    }])->find($ward->zone_id);
                    if ($zone) {
                        $zones = collect([$zone]);
                    }
                }
            }
        }

        $allWardIds = $zones->flatMap(fn($zone) => $zone->wards->pluck('id'))->toArray();
        $allWardNos = $zones->flatMap(fn($zone) => $zone->wards->pluck('ward_no'))->toArray();

        // ─── Hierarchy Statistics ───
        $totalZones = $zones->count();
        $totalWards = count($allWardIds);
        $totalBuildings = $this->getTotalBuildings($allWardIds);
        $totalAssessments = $this->getTotalAssessments($corporation->id);

        // ─── Tax Type Statistics ───
        $misCount = $this->getTotalAssessments($corporation->id);
        $waterTaxCount = $this->getWaterTaxCount($corporation->id);
        $ugdCount = $this->getUgdCount($corporation->id);
        $professionalTaxCount = $this->getProfessionalTaxCount($corporation->id);

        // ─── Survey & Connection Statistics ───
        $surveyedAssessments = $this->getSurveyedAssessments($allWardIds);
        $connectedAssessments = $this->getConnectedAssessments($corporation->id, $allWardIds);

        // ─── Half Year Tax Totals ───
        $misHalfYearTax = $this->getMisHalfYearTax($corporation->id);
        $waterTaxHalfYearTax = $this->getWaterTaxHalfYearTax($corporation->id);
        $ugdHalfYearTax = $this->getUgdHalfYearTax($corporation->id);
        $professionalTaxHalfYearTax = $this->getProfessionalTaxHalfYearTax($corporation->id);
        $totalHalfYearTax = $this->getHalfYearTaxTotal($corporation->id);

        // ─── Balance Totals ───
        $misBalance = $this->getMisBalance($corporation->id);
        $waterTaxBalance = $this->getWaterTaxBalance($corporation->id);
        $ugdBalance = $this->getUgdBalance($corporation->id);
        $professionalTaxBalance = $this->getProfessionalTaxBalance($corporation->id);
        $totalBalance = $misBalance + $waterTaxBalance + $ugdBalance + $professionalTaxBalance;

        $getAllwardBoundary = $this->getAllwardBoundary($corporation->id, $accessibleWardIds);

        // ─── Ward Variation Stats ───
        $wardVariationStats = $this->getWardVariationStats($corporation->id, $zones);

        // ─── Assessment Status ───
        $activeAssessments = $this->getActiveAssessments($corporation->id);
        $notinmis = $this->getNotInMis($corporation->id, $allWardIds);
        $overdueAssessments = $this->getOverdueAssessments($corporation->id);
        $paidAssessments = $this->getPaidAssessments($corporation->id);

        // ─── Stats ───
        $stats = [
            'zones' => $totalZones,
            'wards' => $totalWards,
            'buildings' => $totalBuildings,
            'assessments' => $totalAssessments,
            'owners' => $this->getTotalOwners($corporation->id),
            'active_assessments' => $activeAssessments,
            'notin_mis' => $notinmis,
            'overdue_assessments' => $overdueAssessments,
            'paid_assessments' => $paidAssessments,
            'total_credits' => $totalHalfYearTax,
            'half_year_balance' => $totalBalance,
            'year_collection' => $totalHalfYearTax * 2,
            'total_collection' => $totalHalfYearTax - $totalBalance,
            'surveyed' => $surveyedAssessments,
            'connected' => $connectedAssessments,
            'mis_count' => $misCount,
            'water_tax_count' => $waterTaxCount,
            'ugd_count' => $ugdCount,
            'professional_tax_count' => $professionalTaxCount,
            'total_half_year_tax' => $totalHalfYearTax,
            'total_balance' => $totalBalance,
        ];

        // ─── Tax Breakdown ───
        $taxBreakdown = [
            'mis' => [
                'count' => $misCount,
                'half_year_tax' => $misHalfYearTax,
                'balance' => $misBalance,
                'table' => 'mis_' . $corporation->id,
            ],
            'water_tax' => [
                'count' => $waterTaxCount,
                'half_year_tax' => $waterTaxHalfYearTax,
                'balance' => $waterTaxBalance,
                'table' => 'water_tax_' . $corporation->id,
            ],
            'ugd' => [
                'count' => $ugdCount,
                'half_year_tax' => $ugdHalfYearTax,
                'balance' => $ugdBalance,
                'table' => 'ugd_tax_' . $corporation->id,
            ],
            'professional_tax' => [
                'count' => $professionalTaxCount,
                'half_year_tax' => $professionalTaxHalfYearTax,
                'balance' => $professionalTaxBalance,
                'table' => 'professional_tax_' . $corporation->id,
            ],
        ];

        // ─── Zone Data with Counts ───
        $zoneData = $zones->map(function ($zone) use ($corporation) {
            $wards = $zone->wards;
            $wardIds = $wards->pluck('id')->toArray();
            $wardNos = $wards->pluck('ward_no')->toArray();

            $buildingsCount = $this->getBuildingsByWards($wardIds);
            $assessmentsCount = $this->getTotalAssessmentsByWards($corporation->id, $wardNos);
            $balance = $this->getBalanceByWards($corporation->id, $wardNos);
            $surveyed = $this->getSurveyedByWards($wardIds);
            $connected = $this->getConnectedByWards($corporation->id, $wardIds);

            $zoneWaterTax = $this->getWaterTaxByWards($corporation->id, $wardNos);
            $zoneUgd = $this->getUgdByWards($corporation->id, $wardNos);
            $zoneProfessionalTax = $this->getProfessionalTaxByWards($corporation->id, $wardNos);

            $officer = User::where('role', 'teamleader')
                ->where('zone_id', $zone->id)
                ->where('corporation_id', $corporation->id)
                ->first();

            return [
                'id' => $zone->id,
                'name' => $zone->zone_name,
                'wards' => $wards->count(),
                'buildings' => $buildingsCount,
                'assessments' => $assessmentsCount,
                'surveyed' => $surveyed,
                'connected' => $connected,
                'balance' => $this->formatCurrency($balance),
                'water_tax' => $zoneWaterTax,
                'ugd' => $zoneUgd,
                'professional_tax' => $zoneProfessionalTax,
                'officer' => $officer ? $officer->name : 'Not Assigned',
            ];
        });

        // ─── Zone-wise Tax Summary ───
        $performanceZones = $zones->map(function ($zone) use ($corporation) {
            $wardNos = $zone->wards->pluck('ward_no')->toArray();

            $totalHalfYearTax = $this->getTotalHalfYearTaxByWards($corporation->id, $wardNos);
            $balance = $this->getBalanceByWards($corporation->id, $wardNos);
            $paid = $totalHalfYearTax - $balance;

            return [
                'name' => $zone->zone_name,
                'total_tax' => $this->formatCurrency($totalHalfYearTax),
                'balance' => $this->formatCurrency($balance),
                'paid' => $this->formatCurrency($paid),
            ];
        });

        // ─── Ward Data ───
        $wardData = Ward::whereIn('zone_id', $zones->pluck('id'))
            ->with(['zone'])
            ->take(10)
            ->get()
            ->map(function ($ward) use ($corporation) {
                $wardIds = [$ward->id];
                $wardNos = [$ward->ward_no];
                $buildingsCount = $this->getBuildingsByWards($wardIds);
                $assessmentsCount = $this->getTotalAssessmentsByWards($corporation->id, $wardNos);
                $balance = $this->getBalanceByWards($corporation->id, $wardNos);
                $surveyed = $this->getSurveyedByWards($wardIds);
                $connected = $this->getConnectedByWards($corporation->id, $wardIds);

                $zoneName = 'N/A';
                if ($ward->zone && is_object($ward->zone) && isset($ward->zone->zone_name)) {
                    $zoneName = $ward->zone->zone_name;
                }

                return [
                    'name' => 'Ward ' . $ward->ward_no,
                    'zone' => $zoneName,
                    'buildings' => $buildingsCount,
                    'assessments' => $assessmentsCount,
                    'surveyed' => $surveyed,
                    'connected' => $connected,
                    'balance' => $this->formatCurrency($balance),
                ];
            });

        // ─── Building Data ───
        $buildingData = $this->getBuildingData($allWardIds, 10);
        $assessmentData = $this->getAssessmentData($corporation->id, 10);

        // ─── Tax Data Tables ───
        $waterTaxData = $this->getWaterTaxData($corporation->id, 5);
        $ugdData = $this->getUgdData($corporation->id, 5);
        $professionalTaxData = $this->getProfessionalTaxData($corporation->id, 5);

        // ─── Activities ───
        $activities = $this->getRecentActivities($corporation->id, $accessibleWardIds);

        // ─── Hierarchy Stats ───
        $hierarchyStats = [
            'zones' => $totalZones,
            'wards' => $totalWards,
            'buildings' => $totalBuildings,
            'assessments' => $totalAssessments,
            'surveyed' => $surveyedAssessments,
            'connected' => $connectedAssessments,
        ];

        return view('main.Commissioner.dashboard', compact(
            'stats',
            'zoneData',
            'wardData',
            'buildingData',
            'assessmentData',
            'performanceZones',
            'activities',
            'hierarchyStats',
            'corporation',
            'user',
            'taxBreakdown',
            'waterTaxData',
            'ugdData',
            'professionalTaxData',
            'getAllwardBoundary',
            'wardVariationStats'
        ));
    }

    /**
     * Get all ward boundaries with role-based access
     */
    private function getAllwardBoundary($corporationId, $accessibleWardIds = null)
    {
        $boundaries = [];
        try {
            $wardQuery = Ward::where('zone_id', '!=', null);

            if ($accessibleWardIds !== null) {
                $wardQuery->whereIn('id', $accessibleWardIds);
            } else {
                $zones = Zone::where('corp_id', $corporationId)->get();
                $zoneIds = $zones->pluck('id')->toArray();
                $wardQuery->whereIn('zone_id', $zoneIds);
            }

            $wards = $wardQuery->get();

            foreach ($wards as $ward) {
                if (empty($ward->boundary)) {
                    continue;
                }
                if (is_array($ward->boundary)) {
                    $boundary = $ward->boundary;
                } elseif (is_string($ward->boundary)) {
                    $boundary = json_decode($ward->boundary, true);
                } else {
                    $boundary = [];
                }
                $boundaries[] = [
                    'ward_id'  => $ward->id,
                    'ward_no'  => $ward->ward_no,
                    'boundary' => $boundary,
                ];
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return [];
        }
        return $boundaries;
    }

    // ════════════════════════════════════════════════════════════════
    // MAP VIEW METHODS
    // ════════════════════════════════════════════════════════════════

    public function map()
    {
        $user = Auth::user();

        // Get the first accessible ward for the map view
        $accessibleWardIds = $this->getAccessibleWardIds();

        if (empty($accessibleWardIds)) {
            return redirect()->back()->with('error', 'No wards accessible for your role.');
        }

        $wardId = $accessibleWardIds[0];
        return redirect()->route('commissioner.ward.showmap', ['id' => $wardId]);
    }

    public function showMap($id)
    {
        $user = Auth::user();
        $wardId = $id;

        // Check if user has access to this ward
        $accessibleWardIds = $this->getAccessibleWardIds();
        if (!in_array($wardId, $accessibleWardIds)) {
            abort(403, 'You do not have access to this ward.');
        }

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

        $polygons = DB::table($polygonsTableName)->get();
        $lines = DB::table($linesTableName)->get();
        $points = DB::table($pointsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        $misData = DB::table($misTableName . ' as mis')
            ->leftJoin($waterTaxTableName . ' as wt', 'mis.assessment', '=', 'wt.assessment')
            ->leftJoin($ugdtable . ' as ugd', 'mis.assessment', '=', 'ugd.assessment')
            ->leftJoin($prefessionaltax . ' as pt', 'mis.assessment', '=', 'pt.assessment')
            ->where('mis.ward_no', $wardNo)
            ->select(
                'mis.*',
                'wt.watertax_no',
                'wt.old_watertax_no',
                'ugd.ugd_no',
                'ugd.old_ugd_no',
                'pt.pt_number',
                'pt.old_pt_number'
            )
            ->get();

        $uniqueRoadNames = DB::table($misTableName)
            ->select('road_name')
            ->whereNotNull('road_name')
            ->where('road_name', '!=', '')
            ->distinct()
            ->orderBy('road_name')
            ->pluck('road_name');

        // ─────────────────────────────────────────────────────────
        // ANALYTICS
        // ─────────────────────────────────────────────────────────
        $analytics = $this->buildWardAnalytics($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingData = $this->getBuildingsWithUsageColors($wardId);
        $availableUsages = array_keys($buildingData['usage_counts']);
        sort($availableUsages);
        $areaStats = $this->getAreaVariationStats($wardId, $buildingData['buildings']);

        // Get all accessible wards for navigation
        $accessibleWardIds = $this->getAccessibleWardIds();
        $accessibleWards = Ward::whereIn('id', $accessibleWardIds)->orderBy('ward_no')->get();
        $currentIndex = array_search($wardId, $accessibleWardIds);
        $nextWardId = ($currentIndex !== false && isset($accessibleWardIds[$currentIndex + 1]))
            ? $accessibleWardIds[$currentIndex + 1]
            : null;

        return view('excecutive.mapview', compact(
            'ward',
            'polygons',
            'points',
            'lines',
            'polygonDatas',
            'pointDatas',
            'misData',
            'uniqueRoadNames',
            'analytics',
            'buildingVariations',
            'buildingData',
            'availableUsages',
            'areaStats',
            'accessibleWards',
            'currentIndex',
            'nextWardId'
        ));
    }

    // ─── API METHODS ───

    public function getWardData($wardId)
    {
        $user = Auth::user();

        // Check access
        $accessibleWardIds = $this->getAccessibleWardIds();
        if (!in_array($wardId, $accessibleWardIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ward = Ward::findOrFail($wardId);
        $zoneId = $ward->zone_id;
        $zone = Zone::findOrFail($zoneId);
        $corp = $zone->corp_id;

        $polygonsTableName = "polygons_{$wardId}";
        $linesTableName = "lines_{$wardId}";
        $pointsTableName = "points_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";

        $polygons = DB::table($polygonsTableName)->get();
        $lines = DB::table($linesTableName)->get();
        $points = DB::table($pointsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        return response()->json([
            'ward' => $ward,
            'polygons' => $polygons,
            'lines' => $lines,
            'points' => $points,
            'polygonDatas' => $polygonDatas,
            'pointDatas' => $pointDatas,
        ]);
    }

    public function getInfrastructureData($wardId)
    {
        $user = Auth::user();

        // Check access
        $accessibleWardIds = $this->getAccessibleWardIds();
        if (!in_array($wardId, $accessibleWardIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return infrastructure data - you'll need to implement this based on your data source
        return response()->json([
            'success' => true,
            'data' => [
                'features' => []
            ]
        ]);
    }

    public function updatePolygon(Request $request)
    {
        $user = Auth::user();
        $wardId = $request->ward_id ?? null;

        if ($wardId) {
            $accessibleWardIds = $this->getAccessibleWardIds();
            if (!in_array($wardId, $accessibleWardIds)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Validate request
        $request->validate([
            'gisid' => 'required',
            'coordinates' => 'required',
            'sqfeet' => 'nullable|numeric',
            'ward_id' => 'nullable|integer'
        ]);

        try {
            // Find which ward this polygon belongs to
            $gisid = $request->gisid;
            $wardId = $request->ward_id;

            if (!$wardId) {
                // Find the ward by searching through polygon tables
                $accessibleWardIds = $this->getAccessibleWardIds();
                foreach ($accessibleWardIds as $wid) {
                    $table = "polygons_{$wid}";
                    if (Schema::hasTable($table)) {
                        $exists = DB::table($table)->where('gisid', $gisid)->exists();
                        if ($exists) {
                            $wardId = $wid;
                            break;
                        }
                    }
                }
            }

            if (!$wardId) {
                return response()->json(['error' => 'Ward not found for this GIS ID'], 404);
            }

            $table = "polygons_{$wardId}";
            DB::table($table)
                ->where('gisid', $gisid)
                ->update([
                    'coordinates' => $request->coordinates,
                    'sqfeet' => $request->sqfeet ?? 0,
                    'updated_at' => now(),
                ]);

            // Reload data
            $polygons = DB::table($table)->get();

            return response()->json([
                'success' => true,
                'message' => 'Polygon updated successfully',
                'data' => [
                    'polygons' => $polygons
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function splitPolygon(Request $request)
    {
        $user = Auth::user();
        $wardId = $request->ward_id ?? null;

        if ($wardId) {
            $accessibleWardIds = $this->getAccessibleWardIds();
            if (!in_array($wardId, $accessibleWardIds)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // This is a placeholder - implement polygon splitting logic
        return response()->json([
            'success' => true,
            'message' => 'Polygon split functionality - implement your logic here'
        ]);
    }

    public function saveFeature(Request $request)
    {
        $user = Auth::user();
        $wardId = $request->ward_id ?? null;

        if ($wardId) {
            $accessibleWardIds = $this->getAccessibleWardIds();
            if (!in_array($wardId, $accessibleWardIds)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // This is a placeholder - implement feature saving logic
        return response()->json([
            'success' => true,
            'message' => 'Feature saved - implement your logic here'
        ]);
    }

    public function deleteFeature(Request $request)
    {
        $user = Auth::user();
        $wardId = $request->ward_id ?? null;

        if ($wardId) {
            $accessibleWardIds = $this->getAccessibleWardIds();
            if (!in_array($wardId, $accessibleWardIds)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // This is a placeholder - implement feature deletion logic
        return response()->json([
            'success' => true,
            'message' => 'Feature deleted - implement your logic here'
        ]);
    }

    public function getPointDetails(Request $request)
    {
        $request->validate([
            'gisid'   => 'required',
            'ward_id' => 'required|integer',
        ]);

        $gisid  = $request->gisid;
        $wardId = $request->ward_id;

        // Check access
        $accessibleWardIds = $this->getAccessibleWardIds();
        if (!in_array($wardId, $accessibleWardIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corpId = $zone->corp_id;

        $pointTable = "point_data_{$wardId}";
        $misTable = "mis_{$corpId}";
        $waterTaxTable = "water_tax_{$corpId}";
        $ugdTaxTable = "ugd_tax_{$corpId}";
        $professionalTaxTable = "professional_tax_{$corpId}";

        if (!Schema::hasTable($pointTable)) {
            return response()->json([
                'status' => false,
                'message' => 'Point table not found.'
            ], 404);
        }

        $points = DB::table($pointTable)
            ->where('point_gisid', $gisid)
            ->get();

        if ($points->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No points found.'
            ], 404);
        }

        $results = [];

        foreach ($points as $point) {
            $mis = null;
            if (Schema::hasTable($misTable) && !empty($point->assessment)) {
                $mis = DB::table($misTable)
                    ->where('assessment', $point->assessment)
                    ->first();
            }

            $waterTax = null;
            if (Schema::hasTable($waterTaxTable) && !empty($point->assessment)) {
                $waterTax = DB::table($waterTaxTable)
                    ->where('assessment', $point->assessment)
                    ->first();
            }

            $ugdTax = null;
            if (Schema::hasTable($ugdTaxTable)) {
                $ugdTax = DB::table($ugdTaxTable)
                    ->where('gisid', $point->point_gisid)
                    ->first();
            }

            $professionalTax = collect();
            if (Schema::hasTable($professionalTaxTable) && !empty($point->assessment)) {
                $professionalTax = DB::table($professionalTaxTable)
                    ->where('gisid', $point->point_gisid)
                    ->where('assessment', $point->assessment)
                    ->get();
            }

            $results[] = [
                'point' => $point,
                'mis' => $mis,
                'water_tax' => $waterTax,
                'ugd_tax' => $ugdTax,
                'professional_tax' => $professionalTax,
            ];
        }

        return response()->json([
            'status' => true,
            'gisid' => $gisid,
            'ward_id' => $wardId,
            'total_points' => count($results),
            'data' => $results
        ]);
    }

    public function qcUpdate(Request $request, $id)
    {
        $request->validate([
            'ward_id'     => 'required|integer',
            'qcusage'     => 'nullable|string|max:255',
            'qcsqfeet'    => 'nullable|numeric',
            'qc_remarks'  => 'nullable|string|max:1000',
        ]);

        $wardId = $request->ward_id;

        // Check access
        $accessibleWardIds = $this->getAccessibleWardIds();
        if (!in_array($wardId, $accessibleWardIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pointTable = "point_data_{$wardId}";

        $point = DB::table($pointTable)->where('id', $id)->first();

        if (!$point) {
            return response()->json([
                'message' => 'Point data not found.'
            ], 404);
        }

        DB::table($pointTable)
            ->where('id', $id)
            ->update([
                'qcusage'    => $request->qcusage,
                'qcsqfeet'   => $request->qcsqfeet,
                'qc_remarks' => $request->qc_remarks,
                'updated_at' => now(),
            ]);

        $updatedPoint = DB::table($pointTable)
            ->where('id', $id)
            ->first();

        return response()->json([
            'success'    => true,
            'message'    => 'QC data updated successfully.',
            'point_data' => $updatedPoint,
        ]);
    }

    // ─── HELPER METHODS ───

    private function getWardVariationStats($corporationId, $zones)
    {
        $wardStats = [];
        $misTable = 'mis_' . $corporationId;

        foreach ($zones as $zone) {
            foreach ($zone->wards as $ward) {
                $wardId = $ward->id;
                $wardNo = $ward->ward_no;

                $polygonsTable = 'polygons_' . $wardId;
                $polygonDataTable = 'polygon_data_' . $wardId;
                $pointDataTable = 'point_data_' . $wardId;

                if (!Schema::hasTable($polygonsTable)) {
                    continue;
                }

                try {
                    $polygons = DB::table($polygonsTable)->get();
                    $polygonDatas = Schema::hasTable($polygonDataTable)
                        ? DB::table($polygonDataTable)->get() : collect();
                    $pointDatas = Schema::hasTable($pointDataTable)
                        ? DB::table($pointDataTable)->get() : collect();

                    $misData = collect();
                    if (Schema::hasTable($misTable)) {
                        $misData = DB::table($misTable)->where('ward_no', $wardNo)->get();
                    }

                    $analytics = $this->buildWardAnalytics($polygons, $polygonDatas, $pointDatas, $misData);

                    $wardStats[] = [
                        'ward_id' => $wardId,
                        'ward_no' => $wardNo,
                        'zone_name' => $zone->zone_name,
                        'total_buildings' => $analytics['total_buildings'],
                        'surveyed_buildings' => $analytics['surveyed_buildings'],
                        'survey_percentage' => $analytics['survey_percentage'],
                        'area_variation_count' => $analytics['area_variation_count'],
                        'area_variation_percentage' => $analytics['area_variation_percentage'],
                        'usage_variation_count' => $analytics['usage_variation_count'],
                        'usage_variation_percentage' => $analytics['usage_variation_percentage'],
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        usort($wardStats, function ($a, $b) {
            $scoreA = $a['area_variation_percentage'] + $a['usage_variation_percentage'];
            $scoreB = $b['area_variation_percentage'] + $b['usage_variation_percentage'];
            return $scoreB <=> $scoreA;
        });

        return $wardStats;
    }

    private function buildWardAnalytics($polygons, $polygonDatas, $pointDatas, $misData)
    {
        $totalBuildings = count($polygons);
        $surveyedBuildings = collect($polygonDatas)->pluck('gisid')->unique()->count();
        $totalSurveyedAssessments = count($pointDatas);

        $surveyPercentage = $totalBuildings > 0
            ? round(($surveyedBuildings / $totalBuildings) * 100, 1)
            : 0;

        $polygonDataByGisid = collect($polygonDatas)->keyBy('gisid');
        $misByAssessment = collect($misData)->keyBy('assessment');

        $pointDataByGisid = [];
        foreach ($pointDatas as $pd) {
            $pointDataByGisid[$pd->point_gisid][] = $pd;
        }

        $areaVariationCount = 0;
        $usageVariationCount = 0;
        $validBuildingsCount = 0;
        $totalBuildingArea = 0;
        $totalAssessmentArea = 0;

        foreach ($polygons as $polygon) {
            $gisid = $polygon->gisid;
            $polygonSqfeet = floatval($polygon->sqfeet ?? 0);

            $polyData = $polygonDataByGisid->get($gisid);
            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);
                $buildingArea = ($numberFloor > 0 ? $numberFloor : 1) * $polygonSqfeet;
                if ($basement > 0) {
                    $buildingArea += ($polygonSqfeet * $basement);
                }
                $buildingUsage = $polyData->building_usage ?? null;
            } else {
                $buildingArea = $polygonSqfeet;
                $buildingUsage = null;
            }

            $assessmentArea = 0;
            $hasUsageMismatch = false;

            if (isset($pointDataByGisid[$gisid])) {
                foreach ($pointDataByGisid[$gisid] as $pd) {
                    $mis = $misByAssessment->get($pd->assessment);

                    $pointArea = 0;
                    if (!empty($pd->qcsqfeet) && $pd->qcsqfeet > 0) {
                        $pointArea = floatval($pd->qcsqfeet);
                    } elseif ($mis && !empty($mis->plot_area) && $mis->plot_area > 0) {
                        $pointArea = floatval($mis->plot_area);
                    }
                    $assessmentArea += $pointArea;

                    $pointUsage = $pd->qcusage ?? $pd->bill_usage ?? null;
                    if (
                        $buildingUsage && $pointUsage
                        && strtoupper(trim($buildingUsage)) != strtoupper(trim($pointUsage))
                    ) {
                        $hasUsageMismatch = true;
                    }
                }
            }

            $totalBuildingArea += $buildingArea;
            $totalAssessmentArea += $assessmentArea;

            if ($buildingArea > 0 && $assessmentArea > 0) {
                $validBuildingsCount++;
                if (abs($buildingArea - $assessmentArea) > 1) {
                    $areaVariationCount++;
                }
                if ($hasUsageMismatch) {
                    $usageVariationCount++;
                }
            }
        }

        return [
            'total_buildings' => $totalBuildings,
            'surveyed_buildings' => $surveyedBuildings,
            'total_surveyed_assessments' => $totalSurveyedAssessments,
            'survey_percentage' => $surveyPercentage,
            'area_variation_count' => $areaVariationCount,
            'usage_variation_count' => $usageVariationCount,
            'area_variation_percentage' => $validBuildingsCount > 0
                ? round(($areaVariationCount / $validBuildingsCount) * 100, 1) : 0,
            'usage_variation_percentage' => $validBuildingsCount > 0
                ? round(($usageVariationCount / $validBuildingsCount) * 100, 1) : 0,
            'total_building_area' => round($totalBuildingArea, 2),
            'total_assessment_area' => round($totalAssessmentArea, 2),
        ];
    }

    private function buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData)
    {
        $polygonDataByGisid = collect($polygonDatas)->keyBy('gisid');
        $misByAssessment = collect($misData)->keyBy('assessment');

        $pointDataByGisid = [];
        foreach ($pointDatas as $pd) {
            $pointDataByGisid[$pd->point_gisid][] = $pd;
        }

        $result = [];

        foreach ($polygons as $polygon) {
            $gisid = $polygon->gisid;
            $polygonSqfeet = floatval($polygon->sqfeet ?? 0);

            $polyData = $polygonDataByGisid->get($gisid);
            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);
                $buildingArea = ($numberFloor > 0 ? $numberFloor : 1) * $polygonSqfeet;
                if ($basement > 0) {
                    $buildingArea += ($polygonSqfeet * $basement);
                }
                $buildingUsage = $polyData->building_usage ?? null;
            } else {
                $buildingArea = $polygonSqfeet;
                $buildingUsage = null;
            }

            $assessmentArea = 0;
            $assessmentCount = 0;
            $hasUsageMismatch = false;

            if (isset($pointDataByGisid[$gisid])) {
                foreach ($pointDataByGisid[$gisid] as $pd) {
                    $assessmentCount++;
                    $mis = $misByAssessment->get($pd->assessment);

                    $pointArea = 0;
                    if (!empty($pd->qcsqfeet) && $pd->qcsqfeet > 0) {
                        $pointArea = floatval($pd->qcsqfeet);
                    } elseif ($mis && !empty($mis->plot_area) && $mis->plot_area > 0) {
                        $pointArea = floatval($mis->plot_area);
                    }
                    $assessmentArea += $pointArea;

                    $pointUsage = $pd->qcusage ?? $pd->bill_usage ?? null;
                    if (
                        $buildingUsage && $pointUsage
                        && strtoupper(trim($buildingUsage)) != strtoupper(trim($pointUsage))
                    ) {
                        $hasUsageMismatch = true;
                    }
                }
            }

            $areaVariation = $buildingArea - $assessmentArea;
            $variationPercentage = $buildingArea > 0
                ? round((abs($areaVariation) / $buildingArea) * 100, 1) : 0;

            $result[$gisid] = [
                'gisid' => $gisid,
                'building_area' => round($buildingArea, 2),
                'assessment_area' => round($assessmentArea, 2),
                'area_variation' => round($areaVariation, 2),
                'variation_percentage' => $variationPercentage,
                'area_status' => (abs($areaVariation) > 1) ? 'VARIATION' : 'MATCH',
                'usage_status' => $hasUsageMismatch ? 'VARIATION' : 'MATCH',
                'assessment_count' => $assessmentCount,
            ];
        }

        return $result;
    }

    private function getAreaVariationStats($wardId, $buildings)
    {
        $stats = [
            'min' => 0,
            'max' => 0,
            'avg' => 0,
            'total' => 0,
            'count' => 0,
        ];

        foreach ($buildings as $building) {
            $sqfeet = floatval($building['sqfeet'] ?? 0);
            if ($sqfeet > 0) {
                $stats['total'] += $sqfeet;
                $stats['count']++;
                if ($stats['min'] == 0 || $sqfeet < $stats['min']) {
                    $stats['min'] = $sqfeet;
                }
                if ($sqfeet > $stats['max']) {
                    $stats['max'] = $sqfeet;
                }
            }
        }

        if ($stats['count'] > 0) {
            $stats['avg'] = round($stats['total'] / $stats['count'], 2);
        }

        return $stats;
    }

    private function getBuildingsWithUsageColors($wardId)
    {
        $polygonsTable = "polygons_{$wardId}";
        $polygonDataTable = "polygon_data_{$wardId}";

        if (!Schema::hasTable($polygonsTable)) {
            return ['buildings' => [], 'usage_counts' => [], 'usage_colors' => []];
        }

        $usageColors = [
            'RESIDENTIAL' => '#4CAF50',
            'COMMERCIAL'  => '#2196F3',
            'INDUSTRIAL'  => '#FF9800',
            'INSTITUTIONAL' => '#9C27B0',
            'MIXED'       => '#F44336',
            'GOVERNMENT'  => '#607D8B',
            'VACANT'      => '#FFD700',
            'OTHER'       => '#9E9E9E',
        ];

        $polygons = DB::table($polygonsTable)->get();
        $polygonData = collect();

        if (Schema::hasTable($polygonDataTable)) {
            $polygonData = DB::table($polygonDataTable)->get()->keyBy('gisid');
        }

        $buildings = [];
        $usageCounts = [];

        foreach ($polygons as $polygon) {
            $gisid = $polygon->gisid;
            $buildingData = $polygonData->get($gisid);

            $usage = 'OTHER';
            if ($buildingData && !empty($buildingData->building_usage)) {
                $usage = strtoupper(trim($buildingData->building_usage));
                if (strpos($usage, 'RESIDENT') !== false || strpos($usage, 'DWELLING') !== false) {
                    $usage = 'RESIDENTIAL';
                } elseif (strpos($usage, 'SHOP') !== false || strpos($usage, 'RETAIL') !== false || strpos($usage, 'COMMERCIAL') !== false) {
                    $usage = 'COMMERCIAL';
                } elseif (strpos($usage, 'FACTORY') !== false || strpos($usage, 'MANUFACT') !== false) {
                    $usage = 'INDUSTRIAL';
                } elseif (strpos($usage, 'SCHOOL') !== false || strpos($usage, 'HOSPITAL') !== false || strpos($usage, 'COLLEGE') !== false) {
                    $usage = 'INSTITUTIONAL';
                } elseif (strpos($usage, 'GOV') !== false || strpos($usage, 'OFFICE') !== false || strpos($usage, 'MUNICIPAL') !== false) {
                    $usage = 'GOVERNMENT';
                } elseif (strpos($usage, 'VACANT') !== false || strpos($usage, 'EMPTY') !== false) {
                    $usage = 'VACANT';
                }
            }

            $color = $usageColors[$usage] ?? $usageColors['OTHER'];

            if (!isset($usageCounts[$usage])) {
                $usageCounts[$usage] = 0;
            }
            $usageCounts[$usage]++;

            $buildings[] = [
                'gisid' => $gisid,
                'coordinates' => json_decode($polygon->coordinates, true),
                'usage' => $usage,
                'color' => $color,
                'sqfeet' => $polygon->sqfeet ?? 0,
                'building_data' => $buildingData,
            ];
        }

        return [
            'buildings' => $buildings,
            'usage_counts' => $usageCounts,
            'usage_colors' => $usageColors,
        ];
    }

    private function getRecentActivities($corporationId, $accessibleWardIds)
    {
        $activities = [];
        $tables = [
            'mis_' . $corporationId,
            'water_tax_' . $corporationId,
            'ugd_tax_' . $corporationId,
            'professional_tax_' . $corporationId,
        ];

        $typeLabels = [
            'mis' => 'Assessment',
            'water_tax' => 'Water Tax',
            'ugd_tax' => 'UGD Tax',
            'professional_tax' => 'Professional Tax',
        ];

        $typeColors = [
            'mis' => '#0f6b47',
            'water_tax' => '#1d4ed8',
            'ugd_tax' => '#a9741a',
            'professional_tax' => '#5b21b6',
        ];

        $typeIcons = [
            'mis' => 'clipboard-data',
            'water_tax' => 'droplet',
            'ugd_tax' => 'pipe',
            'professional_tax' => 'briefcase',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $tableType = str_replace('_' . $corporationId, '', $table);
                $typeLabel = $typeLabels[$tableType] ?? ucfirst($tableType);
                $color = $typeColors[$tableType] ?? '#0f6b47';
                $icon = $typeIcons[$tableType] ?? 'file-text';

                $recentItems = DB::table($table)
                    ->orderBy('id', 'desc')
                    ->limit(3)
                    ->get();

                foreach ($recentItems as $item) {
                    $numberField = 'assessment';
                    if ($tableType == 'water_tax') $numberField = 'watertax_no';
                    elseif ($tableType == 'ugd_tax') $numberField = 'ugd_no';
                    elseif ($tableType == 'professional_tax') $numberField = 'pt_number';

                    $itemNo = $item->$numberField ?? $typeLabel . str_pad($item->id, 6, '0', STR_PAD_LEFT);
                    $ownerName = $item->owner_name ?? 'N/A';

                    $statusText = '';
                    if (!empty($item->gisid)) {
                        $statusText = '✓ Completed';
                    } else {
                        $statusText = '⏳ Pending';
                    }

                    $activities[] = [
                        'icon' => $icon,
                        'color' => $color,
                        'text' => '<strong>' . $typeLabel . '</strong> ' . $itemNo . ' - ' . $ownerName . ' (' . $statusText . ')',
                        'time' => $this->getTimeAgo(now()),
                    ];
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // Get entries from point_data tables for accessible wards
        foreach ($accessibleWardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $recentPoints = DB::table($table)
                        ->orderBy('id', 'desc')
                        ->limit(2)
                        ->get();

                    foreach ($recentPoints as $point) {
                        $activities[] = [
                            'icon' => 'pin-map',
                            'color' => '#e11d48',
                            'text' => '<strong>Survey Entry</strong> - Building ' . ($point->building_no ?? 'N/A') . ' surveyed in Ward ' . $wardId,
                            'time' => $this->getTimeAgo(now()),
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip
                }
            }
        }

        return array_slice($activities, 0, 10);
    }

    // ════════════════════════════════════════════════════════════════
    // TAX AND STATISTICS METHODS (existing methods remain the same)
    // ════════════════════════════════════════════════════════════════

    private function getHalfYearTaxTotal($corporationId)
    {
        $tables = [
            'mis_' . $corporationId,
            'water_tax_' . $corporationId,
            'ugd_tax_' . $corporationId,
            'professional_tax_' . $corporationId,
        ];

        $total = 0;

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                if (Schema::hasColumn($table, 'half_year_tax')) {
                    $total += DB::table($table)->sum('half_year_tax');
                } elseif (Schema::hasColumn($table, 'slab_rate')) {
                    $total += DB::table($table)->sum('slab_rate');
                } elseif (Schema::hasColumn($table, 'ugd_tax_amount')) {
                    $total += DB::table($table)->sum('ugd_tax_amount');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $total;
    }

    private function getMisHalfYearTax($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getWaterTaxHalfYearTax($corporationId)
    {
        $table = 'water_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'slab_rate')) {
                return DB::table($table)->sum('slab_rate');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getUgdHalfYearTax($corporationId)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'ugd_tax_amount')) {
                return DB::table($table)->sum('ugd_tax_amount');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getProfessionalTaxHalfYearTax($corporationId)
    {
        $table = 'professional_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getMisBalance($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)->sum('balance');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getWaterTaxBalance($corporationId)
    {
        $table = 'water_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)->sum('balance');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getUgdBalance($corporationId)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)->sum('balance');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getProfessionalTaxBalance($corporationId)
    {
        $table = 'professional_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)->sum('balance');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getTotalBuildings($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = "polygons_{$wardId}";
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getBuildingsByWards($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = "polygons_{$wardId}";
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getTotalAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (Schema::hasTable($table)) {
            try {
                return DB::table($table)->count();
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    private function getTotalAssessmentsByWards($corporationId, $wardNos)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getBalanceByWards($corporationId, $wardNos)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->whereIn('ward_no', $wardNos)
                    ->sum('balance');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getTotalHalfYearTaxByWards($corporationId, $wardNos)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)
                    ->whereIn('ward_no', $wardNos)
                    ->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getSurveyedAssessments($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getSurveyedByWards($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getConnectedAssessments($corporationId, $wardIds)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }

        $gisids = [];
        try {
            if (Schema::hasColumn($misTable, 'gisid')) {
                $gisids = DB::table($misTable)->pluck('gisid')->filter()->toArray();
            }
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($gisids)) {
            return 0;
        }

        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'gisid')) {
                try {
                    $total += DB::table($table)
                        ->whereIn('gisid', $gisids)
                        ->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getConnectedByWards($corporationId, $wardIds)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }

        $gisids = [];
        try {
            if (Schema::hasColumn($misTable, 'gisid')) {
                $gisids = DB::table($misTable)->pluck('gisid')->filter()->toArray();
            }
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($gisids)) {
            return 0;
        }

        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'gisid')) {
                try {
                    $total += DB::table($table)
                        ->whereIn('gisid', $gisids)
                        ->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getActiveAssessments($corporationId)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }
        return DB::table($misTable)->count();
    }

    private function getPaidAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->where('balance', '=', 0)
                    ->count();
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getOverdueAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->where('balance', '>', 0)
                    ->count();
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getNotInMis($corporationId, $wardIds)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }

        $assessments = [];
        try {
            $assessments = DB::table($misTable)->pluck('assessment')->filter()->toArray();
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($assessments)) {
            return 0;
        }

        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)
                        ->whereNotIn('assessment', $assessments)
                        ->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getTotalOwners($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            if (Schema::hasColumn($table, 'owner_name')) {
                $owners = DB::table($table)->pluck('owner_name')->filter()->toArray();
                return count(array_unique($owners));
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getWaterTaxCount($corporationId)
    {
        $table = 'water_tax_' . $corporationId;
        if (Schema::hasTable($table)) {
            try {
                return DB::table($table)->count();
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    private function getUgdCount($corporationId)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (Schema::hasTable($table)) {
            try {
                return DB::table($table)->count();
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    private function getProfessionalTaxCount($corporationId)
    {
        $table = 'professional_tax_' . $corporationId;
        if (Schema::hasTable($table)) {
            try {
                return DB::table($table)->count();
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    private function getWaterTaxByWards($corporationId, $wardNos)
    {
        $table = 'water_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getUgdByWards($corporationId, $wardNos)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getProfessionalTaxByWards($corporationId, $wardNos)
    {
        $table = 'professional_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAssessmentData($corporationId, $limit = 10)
    {
        $assessments = [];
        $table = 'mis_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $assessments;
        }

        try {
            $columns = Schema::getColumnListing($table);
            $select = ['id'];

            if (in_array('assessment', $columns)) $select[] = 'assessment';
            if (in_array('owner_name', $columns)) $select[] = 'owner_name';
            if (in_array('new_door_no', $columns)) $select[] = 'new_door_no';
            if (in_array('old_door_no', $columns)) $select[] = 'old_door_no';
            if (in_array('type', $columns)) $select[] = 'type';
            if (in_array('half_year_tax', $columns)) $select[] = 'half_year_tax';
            if (in_array('balance', $columns)) $select[] = 'balance';
            if (in_array('gisid', $columns)) $select[] = 'gisid';
            if (in_array('ward_no', $columns)) $select[] = 'ward_no';
            if (in_array('road_name', $columns)) $select[] = 'road_name';

            $results = DB::table($table)
                ->select($select)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $assessment) {
                $status = 'pending';
                if (isset($assessment->balance) && $assessment->balance == 0) {
                    $status = 'paid';
                } elseif (isset($assessment->balance) && $assessment->balance > 0) {
                    $status = 'overdue';
                }

                $assessments[] = [
                    'no' => $assessment->assessment ?? 'AST' . str_pad($assessment->id, 6, '0', STR_PAD_LEFT),
                    'owner' => $assessment->owner_name ?? 'N/A',
                    'building' => $assessment->new_door_no ?? $assessment->old_door_no ?? 'N/A',
                    'type' => $assessment->type ?? 'N/A',
                    'tax' => $this->formatCurrency($assessment->half_year_tax ?? 0),
                    'status' => $status,
                    'gis_id' => $assessment->gisid ?? null,
                    'ward' => $assessment->ward_no ?? 'N/A',
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $assessments;
    }

    private function getBuildingData($wardIds, $limit = 10)
    {
        $buildings = [];
        $count = 0;

        foreach ($wardIds as $wardId) {
            if ($count >= $limit) break;

            $table = "polygons_{$wardId}";
            if (Schema::hasTable($table)) {
                try {
                    $columns = Schema::getColumnListing($table);
                    $select = [];

                    if (in_array('gisid', $columns)) $select[] = 'gisid';
                    if (in_array('sqfeet', $columns)) $select[] = 'sqfeet';
                    if (in_array('coordinates', $columns)) $select[] = 'coordinates';

                    if (empty($select)) {
                        $select = ['id'];
                    }

                    $results = DB::table($table)
                        ->select($select)
                        ->limit($limit - $count)
                        ->get();

                    foreach ($results as $polygon) {
                        $buildings[] = [
                            'gisid' => $polygon->gisid ?? $polygon->id,
                            'sqfeet' => $polygon->sqfeet ?? 0,
                            'ward_id' => $wardId,
                        ];
                        $count++;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $buildings;
    }

    private function getWaterTaxData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'water_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $results = DB::table($table)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gisid)) ? 'paid' : 'pending';
                $amount = $item->slab_rate ?? 0;

                $data[] = [
                    'no' => $item->watertax_no ?? 'WT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($amount),
                    'status' => $status,
                    'gis_id' => $item->gisid ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    private function getUgdData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'ugd_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $results = DB::table($table)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gisid)) ? 'paid' : 'pending';
                $amount = $item->ugd_tax_amount ?? 0;

                $data[] = [
                    'no' => $item->ugd_no ?? 'UGD' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($amount),
                    'status' => $status,
                    'gis_id' => $item->gisid ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    private function getProfessionalTaxData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'professional_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $results = DB::table($table)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gisid)) ? 'paid' : 'pending';
                $amount = $item->half_year_tax ?? 0;

                $data[] = [
                    'no' => $item->pt_number ?? 'PT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($amount),
                    'status' => $status,
                    'gis_id' => $item->gisid ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    // ─── HELPER METHODS ───

    private function formatCurrency($amount)
    {
        if (!$amount) return '₹0';
        $amount = (int)$amount;
        if ($amount >= 10000000) {
            return '₹' . number_format($amount / 10000000, 2) . ' Cr';
        } elseif ($amount >= 100000) {
            return '₹' . number_format($amount / 100000, 2) . ' L';
        } elseif ($amount >= 1000) {
            return '₹' . number_format($amount / 1000, 1) . 'K';
        }
        return '₹' . number_format($amount);
    }

    private function getTimeAgo($timestamp)
    {
        if (!$timestamp) return 'N/A';
        try {
            $diff = now()->diffInSeconds($timestamp);
            if ($diff < 60) return $diff . ' seconds ago';
            if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
            if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
            if ($diff < 604800) return floor($diff / 86400) . ' days ago';
            return date('M d, Y', strtotime($timestamp));
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getEmptyStats()
    {
        return [
            'zones' => 0,
            'wards' => 0,
            'buildings' => 0,
            'assessments' => 0,
            'owners' => 0,
            'active_assessments' => 0,
            'notin_mis' => 0,
            'overdue_assessments' => 0,
            'paid_assessments' => 0,
            'total_credits' => 0,
            'half_year_balance' => 0,
            'year_collection' => 0,
            'total_collection' => 0,
            'surveyed' => 0,
            'connected' => 0,
            'mis_count' => 0,
            'water_tax_count' => 0,
            'ugd_count' => 0,
            'professional_tax_count' => 0,
            'total_half_year_tax' => 0,
            'total_balance' => 0,
        ];
    }

    private function getEmptyHierarchyStats()
    {
        return [
            'zones' => 0,
            'wards' => 0,
            'buildings' => 0,
            'assessments' => 0,
            'surveyed' => 0,
            'connected' => 0,
        ];
    }

    private function getEmptyTaxBreakdown()
    {
        return [
            'mis' => ['count' => 0, 'half_year_tax' => 0, 'balance' => 0, 'table' => ''],
            'water_tax' => ['count' => 0, 'half_year_tax' => 0, 'balance' => 0, 'table' => ''],
            'ugd' => ['count' => 0, 'half_year_tax' => 0, 'balance' => 0, 'table' => ''],
            'professional_tax' => ['count' => 0, 'half_year_tax' => 0, 'balance' => 0, 'table' => ''],
        ];
    }
}
