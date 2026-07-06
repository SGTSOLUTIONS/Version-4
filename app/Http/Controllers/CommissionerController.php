<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\Zone;
use App\Models\Ward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionerController extends Controller
{
    public function dashboard()
    {
        // Get the logged-in commissioner
        $user = auth()->user();

        // Commissioner only sees their corporation
        $corporation = Corporation::with(['zones.wards'])->find($user->corporation_id);

        if (!$corporation) {
            return view('commissioner.dashboard', [
                'error' => 'No corporation assigned to your account. Please contact administrator.',
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
            ]);
        }

        // Get zones for the corporation
        $zones = $corporation->zones()->with(['wards'])->get();
        $allWardIds = $zones->flatMap(fn($zone) => $zone->wards->pluck('id'))->toArray();

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
        $allwardBoundary = $this->getAllwardBoundary($corporation->id);
        $connectedAssessments = $this->getConnectedAssessments($corporation->id, $allWardIds);

        // ─── Collection Statistics ───
        $totalcredits = $this->getTotalCredits($corporation->id);
        $halfyearbalance = $this->getHalfYearBalance($corporation->id);
        $yearCollection = $this->getYearCollection($corporation->id);
        $totalCollection = $this->getTotalCollection($corporation->id);

        // ─── Tax-wise Collection ───
        $waterTaxCollection = $this->getWaterTaxCollection($corporation->id);
        $ugdCollection = $this->getUgdCollection($corporation->id);
        $professionalTaxCollection = $this->getProfessionalTaxCollection($corporation->id);
        $misCollection = $this->getMisCollection($corporation->id);

        // ─── Assessment Status ───
        $activeAssessments = $this->getActiveAssessments($corporation->id, $allWardIds);
        $notinmis = $this->getNotInMis($corporation->id, $allWardIds);
        $overdueAssessments = $this->getOverdueAssessments($corporation->id);
        $paidAssessments = $this->getPaidAssessments($corporation->id);

        // ─── Top Statistics ───
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
            'total_credits' => $totalcredits,
            'half_year_balance' => $halfyearbalance,
            'year_collection' => $yearCollection,
            'total_collection' => $totalCollection,
            'surveyed' => $surveyedAssessments,
            'connected' => $connectedAssessments,
            'mis_count' => $misCount,
            'water_tax_count' => $waterTaxCount,
            'ugd_count' => $ugdCount,
            'professional_tax_count' => $professionalTaxCount,
        ];

        // ─── Tax Breakdown ───
        $taxBreakdown = [
            'mis' => [
                'count' => $misCount,
                'collection' => $misCollection,
                'table' => 'mis_' . $corporation->id,
            ],
            'water_tax' => [
                'count' => $waterTaxCount,
                'collection' => $waterTaxCollection,
                'table' => 'water_tax_' . $corporation->id,
            ],
            'ugd' => [
                'count' => $ugdCount,
                'collection' => $ugdCollection,
                'table' => 'ugd_tax_' . $corporation->id,
            ],
            'professional_tax' => [
                'count' => $professionalTaxCount,
                'collection' => $professionalTaxCollection,
                'table' => 'professional_tax_' . $corporation->id,
            ],
        ];

        // ─── Zone Data with Counts ───
        $zoneData = $zones->map(function ($zone) use ($corporation) {
            $wards = $zone->wards;
            $wardIds = $wards->pluck('id')->toArray();

            $buildingsCount = $this->getBuildingsByWards($wardIds);
            $assessmentsCount = $this->getTotalAssessmentsByWards($corporation->id, $wardIds);
            $collection = $this->getCollectionByWards($corporation->id, $wardIds);
            $pending = $this->getPendingByWards($corporation->id, $wardIds);
            $surveyed = $this->getSurveyedByWards($wardIds);
            $connected = $this->getConnectedByWards($corporation->id, $wardIds);

            // Tax-wise counts for zone
            $zoneWaterTax = $this->getWaterTaxByWards($corporation->id, $wardIds);
            $zoneUgd = $this->getUgdByWards($corporation->id, $wardIds);
            $zoneProfessionalTax = $this->getProfessionalTaxByWards($corporation->id, $wardIds);

            // Get team leader/officer for this zone
            $officer = User::where('role', 'teamleader')
                ->where('zone_id', $zone->id)
                ->where('corporation_id', $corporation->id)
                ->first();
    return response()->json($allwardBoundary);
            return [
                'id' => $zone->id,
                'name' => $zone->zone_name,
                'wards' => $wards->count(),
                'buildings' => $buildingsCount,
                'assessments' => $assessmentsCount,
                'surveyed' => $surveyed,
                'connected' => $connected,
                'collection' => $this->formatCurrency($collection),
                'pending' => $pending,
                'water_tax' => $zoneWaterTax,
                'ugd' => $zoneUgd,
                'professional_tax' => $zoneProfessionalTax,
                'officer' => $officer ? $officer->name : 'Not Assigned',
            ];
        });

        // ─── Ward Data ───
        $wardData = Ward::whereIn('zone_id', $zones->pluck('id'))
            ->with(['zone'])
            ->take(10)
            ->get()
            ->map(function ($ward) use ($corporation) {
                $wardIds = [$ward->id];
                $buildingsCount = $this->getBuildingsByWards($wardIds);
                $assessmentsCount = $this->getTotalAssessmentsByWards($corporation->id, $wardIds);
                $collection = $this->getCollectionByWards($corporation->id, $wardIds);
                $pending = $this->getPendingByWards($corporation->id, $wardIds);
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
                    'collection' => $this->formatCurrency($collection),
                    'pending' => $pending,
                ];
            });

        // ─── Building Data (from polygon tables) ───
        $buildingData = $this->getBuildingData($allWardIds, 10);

        // ─── Assessment Data (from MIS table) ───
        $assessmentData = $this->getAssessmentData($corporation->id, 10);

        // ─── Tax Data Tables ───
        $waterTaxData = $this->getWaterTaxData($corporation->id, 5);
        $ugdData = $this->getUgdData($corporation->id, 5);
        $professionalTaxData = $this->getProfessionalTaxData($corporation->id, 5);

        // ─── Zone Performance Data ───
        $performanceZones = $zones->map(function ($zone) use ($corporation) {
            $wardIds = $zone->wards->pluck('id')->toArray();

            $target = $zone->target ?? 10000000;
            $collected = $this->getCollectionByWards($corporation->id, $wardIds);
            $pending = $this->getPendingByWards($corporation->id, $wardIds);
            $achievement = $target > 0 ? round(($collected / $target) * 100) : 0;

            return [
                'name' => $zone->zone_name,
                'target' => $this->formatCurrency($target),
                'collected' => $this->formatCurrency($collected),
                'pending' => $this->formatCurrency($pending),
                'achievement' => min(100, $achievement),
            ];
        });

        // ─── Recent Activities ───
        $activities = $this->getRecentActivities($corporation->id);

        // ─── Quick Stats for Hierarchy ───
        $hierarchyStats = [
            'zones' => $totalZones,
            'wards' => $totalWards,
            'buildings' => $totalBuildings,
            'assessments' => $totalAssessments,
            'surveyed' => $surveyedAssessments,
            'connected' => $connectedAssessments,
        ];

        return view('main.commissioner.dashboard', compact(
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
            'professionalTaxData'
        ));
    }

    /**
     * Get empty stats (for error state)
     */
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
        ];
    }

    /**
     * Get empty hierarchy stats
     */
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

    /**
     * Get empty tax breakdown
     */
    private function getEmptyTaxBreakdown()
    {
        return [
            'mis' => ['count' => 0, 'collection' => 0, 'table' => ''],
            'water_tax' => ['count' => 0, 'collection' => 0, 'table' => ''],
            'ugd' => ['count' => 0, 'collection' => 0, 'table' => ''],
            'professional_tax' => ['count' => 0, 'collection' => 0, 'table' => ''],
        ];
    }

    // ─── Building Methods ───

    /**
     * Get total buildings from polygon tables across all wards
     */
    private function getTotalBuildings($wardIds)
    {
        $total = 0;

        foreach ($wardIds as $wardId) {
            $tables = $this->getWardPolygonTables($wardId);

            // polygons_{wardId}
            if (Schema::hasTable($tables[0])) {
                $total += DB::table($tables[0])->count();
            }
        }

        return $total;
    }
    /**
     * Get buildings by ward IDs (from polygon tables)
     */
    private function getBuildingsByWards($wardIds)
    {
        $total = 0;

        foreach ($wardIds as $wardId) {
            $tables = $this->getWardPolygonTables($wardId);
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    try {
                        $total += DB::table($table)->count();
                    } catch (\Exception $e) {
                        // Table exists but might have issues
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Get building data for display (from polygon tables)
     */
    private function getBuildingData($wardIds, $limit = 10)
    {
        $buildings = [];
        $count = 0;

        foreach ($wardIds as $wardId) {
            if ($count >= $limit) break;

            $tables = $this->getWardPolygonTables($wardId);
            foreach ($tables as $table) {
                if ($count >= $limit) break;

                if (Schema::hasTable($table)) {
                    try {
                        $columns = Schema::getColumnListing($table);
                        $select = [];

                        if (in_array('building_no', $columns)) $select[] = 'building_no';
                        if (in_array('ward_id', $columns)) $select[] = 'ward_id';
                        if (in_array('type', $columns)) $select[] = 'type';
                        if (in_array('floors', $columns)) $select[] = 'floors';
                        if (in_array('owner_name', $columns)) $select[] = 'owner_name';

                        if (empty($select)) {
                            $select = ['id'];
                        }

                        $results = DB::table($table)
                            ->select($select)
                            ->limit($limit - $count)
                            ->get();

                        foreach ($results as $building) {
                            $ward = isset($building->ward_id) ? Ward::find($building->ward_id) : null;
                            $buildings[] = [
                                'building_no' => $building->building_no ?? $building->id ?? 'N/A',
                                'ward' => $ward ? 'Ward ' . $ward->ward_no : 'Ward ' . $wardId,
                                'type' => $building->type ?? 'N/A',
                                'floors' => $building->floors ?? 0,
                                'owner' => $building->owner_name ?? 'N/A',
                            ];
                            $count++;
                        }
                    } catch (\Exception $e) {
                        // Skip this table if there's an error
                    }
                }
            }
        }

        return $buildings;
    }

    /**
     * Get ward polygon tables
     */
    private function getWardPolygonTables($wardId)
    {
        return [
            'polygons_' . $wardId,
            'polygon_data_' . $wardId,
        ];
    }

    // ─── Assessment Methods ───

    /**
     * Get total assessments from MIS table
     */
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

    /**
     * Get total assessments by wards from MIS table
     */
    private function getTotalAssessmentsByWards($corporationId, $wardIds)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            return DB::table($table)->whereIn('ward_id', $wardIds)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get assessment data from MIS
     */
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
            if (in_array('assessment_no', $columns)) $select[] = 'assessment_no';
            if (in_array('owner_name', $columns)) $select[] = 'owner_name';
            if (in_array('building_no', $columns)) $select[] = 'building_no';
            if (in_array('type', $columns)) $select[] = 'type';
            if (in_array('amount', $columns)) $select[] = 'amount';
            if (in_array('tax', $columns)) $select[] = 'tax as amount';
            if (in_array('status', $columns)) $select[] = 'status';
            if (in_array('created_at', $columns)) $select[] = 'created_at';
            if (in_array('gis_id', $columns)) $select[] = 'gis_id';
            if (in_array('ward_id', $columns)) $select[] = 'ward_id';
            if (in_array('paid_at', $columns)) $select[] = 'paid_at';

            $results = DB::table($table)
                ->select($select)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $assessment) {
                $ward = isset($assessment->ward_id) ? Ward::find($assessment->ward_id) : null;
                $assessments[] = [
                    'no' => $assessment->assessment_no ?? 'AST' . str_pad($assessment->id, 6, '0', STR_PAD_LEFT),
                    'owner' => $assessment->owner_name ?? 'N/A',
                    'building' => $assessment->building_no ?? 'N/A',
                    'type' => $assessment->type ?? 'N/A',
                    'tax' => $this->formatCurrency($assessment->amount ?? 0),
                    'status' => $assessment->status ?? 'pending',
                    'gis_id' => $assessment->gis_id ?? null,
                    'ward' => $ward ? 'Ward ' . $ward->ward_no : 'N/A',
                    'paid_at' => $assessment->paid_at ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $assessments;
    }

    /**
     * Get active assessments from MIS
     */
    private function getActiveAssessments($corporationId)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }



        $total = DB::table($misTable)->count();


        return $total;
    }

    /**
     * Get pending assessments from MIS
     */
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
                    // Skip if error
                }
            }
        }

        return $total;
    }

    /**
     * Get overdue assessments from MIS
     */
    private function getOverdueAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->where('balance', '>', 1000)
                    ->count();
            }
        } catch (\Exception $e) {
            // Column might not exist
        }

        return 0;
    }

    /**
     * Get paid assessments from MIS
     */
    private function getPaidAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->where(function ($query) {
                        $query->where('balance', 0)
                            ->orWhereNull('balance');
                    })
                    ->count();
            }
        } catch (\Exception $e) {
            // Column might not exist
        }

        return 0;
    }

    // ─── Survey Methods ───

    /**
     * Get surveyed assessments from point_data tables
     */
    private function getSurveyedAssessments($wardIds)
    {
        $total = 0;

        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    // Table exists but might have issues
                }
            }
        }

        return $total;
    }

    /**
     * Get surveyed by ward IDs (from point_data)
     */
    private function getSurveyedByWards($wardIds)
    {
        $total = 0;

        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    // Table exists but might have issues
                }
            }
        }

        return $total;
    }

    /**
     * Get connected assessments (point_data with matching gis_id in MIS)
     */
    private function getConnectedAssessments($corporationId, $wardIds)
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
                        ->whereIn('assessment', $assessments)
                        ->count();
                } catch (\Exception $e) {
                    // Skip if error
                }
            }
        }

        return $total;
    }
    private function getAllwardBoundary($corporationId)
    {
        $table = 'ward_' . $corporationId;
        $totalBoundaries = [];

        if (Schema::hasTable($table)) {
            try {

                $wards = DB::table($table)->get();

                foreach ($wards as $ward) {

                    if (!empty($ward->boundary)) {
                        $totalBoundaries[] = $ward->boundary;
                    }
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        return $totalBoundaries;
    }
    /**
     * Get connected by ward IDs (point_data with matching gis_id in MIS)
     */
    private function getConnectedByWards($corporationId, $wardIds)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }

        if (!Schema::hasColumn($misTable, 'gis_id')) {
            return 0;
        }

        $total = 0;
        $gisIds = [];

        try {
            $gisIds = DB::table($misTable)->pluck('gis_id')->filter()->toArray();
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($gisIds)) {
            return 0;
        }

        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'gis_id')) {
                try {
                    $total += DB::table($table)
                        ->whereIn('gis_id', $gisIds)
                        ->count();
                } catch (\Exception $e) {
                    // Skip if error
                }
            }
        }

        return $total;
    }

    // ─── Tax Type Count Methods ───

    /**
     * Get Water Tax count
     */
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

    /**
     * Get UGD count
     */
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

    /**
     * Get Professional Tax count
     */
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

    /**
     * Get Water Tax by wards
     */
    private function getWaterTaxByWards($corporationId, $wardIds)
    {
        $table = 'water_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            return DB::table($table)->whereIn('ward_id', $wardIds)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get UGD by wards
     */
    private function getUgdByWards($corporationId, $wardIds)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            return DB::table($table)->whereIn('ward_id', $wardIds)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get Professional Tax by wards
     */
    private function getProfessionalTaxByWards($corporationId, $wardIds)
    {
        $table = 'professional_tax_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            return DB::table($table)->whereIn('ward_id', $wardIds)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    // ─── Collection Methods ───

    /**
     * Get today's collection from MIS
     */
    private function getTotalCredits($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)
                    ->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            // Columns might not exist
        }

        return 0;
    }

    /**
     * Get month collection
     */
    private function getHalfYearBalance($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'balance')) {
                return DB::table($table)
                    ->sum('balance');
            }
        } catch (\Exception $e) {
            // Column might not exist
        }

        return 0;
    }

    /**
     * Get year collection
     */
    private function getYearCollection($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                $halfYearTotal = DB::table($table)->sum('half_year_tax');

                return $halfYearTotal * 2;
            }
        } catch (\Exception $e) {
            // Columns might not exist
        }

        return 0;
    }

    /**
     * Get total collection
     */
    private function getTotalCollection($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            // Columns might not exist
        }

        return 0;
    }

    /**
     * Get collection by ward IDs
     */
    private function getCollectionByWards($corporationId, $wardIds)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->whereIn('ward_id', $wardIds)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            // Columns might not exist
        }

        return 0;
    }

    /**
     * Get pending by ward IDs
     */
    private function getPendingByWards($corporationId, $wardIds)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status')) {
                return DB::table($table)
                    ->whereIn('ward_id', $wardIds)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->count();
            }
        } catch (\Exception $e) {
            // Columns might not exist
        }

        return 0;
    }

    /**
     * Get MIS collection
     */
    private function getMisCollection($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    /**
     * Get Water Tax collection
     */
    private function getWaterTaxCollection($corporationId)
    {
        $table = 'water_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    /**
     * Get UGD collection
     */
    private function getUgdCollection($corporationId)
    {
        $table = 'ugd_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    /**
     * Get Professional Tax collection
     */
    private function getProfessionalTaxCollection($corporationId)
    {
        $table = 'professional_tax_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'amount')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    // ─── Tax Data Methods ───

    /**
     * Get Water Tax data for display
     */
    private function getWaterTaxData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'water_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $columns = Schema::getColumnListing($table);
            $select = ['id'];

            if (in_array('assessment_no', $columns)) $select[] = 'assessment_no';
            if (in_array('owner_name', $columns)) $select[] = 'owner_name';
            if (in_array('building_no', $columns)) $select[] = 'building_no';
            if (in_array('amount', $columns)) $select[] = 'amount';
            if (in_array('status', $columns)) $select[] = 'status';
            if (in_array('created_at', $columns)) $select[] = 'created_at';
            if (in_array('ward_id', $columns)) $select[] = 'ward_id';

            $results = DB::table($table)
                ->select($select)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $ward = isset($item->ward_id) ? Ward::find($item->ward_id) : null;
                $data[] = [
                    'no' => $item->assessment_no ?? 'WT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'owner' => $item->owner_name ?? 'N/A',
                    'building' => $item->building_no ?? 'N/A',
                    'amount' => $this->formatCurrency($item->amount ?? 0),
                    'status' => $item->status ?? 'pending',
                    'ward' => $ward ? 'Ward ' . $ward->ward_no : 'N/A',
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    /**
     * Get UGD data for display
     */
    private function getUgdData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'ugd_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $columns = Schema::getColumnListing($table);
            $select = ['id'];

            if (in_array('assessment_no', $columns)) $select[] = 'assessment_no';
            if (in_array('owner_name', $columns)) $select[] = 'owner_name';
            if (in_array('building_no', $columns)) $select[] = 'building_no';
            if (in_array('amount', $columns)) $select[] = 'amount';
            if (in_array('status', $columns)) $select[] = 'status';
            if (in_array('created_at', $columns)) $select[] = 'created_at';
            if (in_array('ward_id', $columns)) $select[] = 'ward_id';

            $results = DB::table($table)
                ->select($select)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $ward = isset($item->ward_id) ? Ward::find($item->ward_id) : null;
                $data[] = [
                    'no' => $item->assessment_no ?? 'UGD' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'owner' => $item->owner_name ?? 'N/A',
                    'building' => $item->building_no ?? 'N/A',
                    'amount' => $this->formatCurrency($item->amount ?? 0),
                    'status' => $item->status ?? 'pending',
                    'ward' => $ward ? 'Ward ' . $ward->ward_no : 'N/A',
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    /**
     * Get Professional Tax data for display
     */
    private function getProfessionalTaxData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'professional_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $columns = Schema::getColumnListing($table);
            $select = ['id'];

            if (in_array('assessment_no', $columns)) $select[] = 'assessment_no';
            if (in_array('owner_name', $columns)) $select[] = 'owner_name';
            if (in_array('building_no', $columns)) $select[] = 'building_no';
            if (in_array('amount', $columns)) $select[] = 'amount';
            if (in_array('status', $columns)) $select[] = 'status';
            if (in_array('created_at', $columns)) $select[] = 'created_at';
            if (in_array('ward_id', $columns)) $select[] = 'ward_id';

            $results = DB::table($table)
                ->select($select)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $ward = isset($item->ward_id) ? Ward::find($item->ward_id) : null;
                $data[] = [
                    'no' => $item->assessment_no ?? 'PT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'owner' => $item->owner_name ?? 'N/A',
                    'building' => $item->building_no ?? 'N/A',
                    'amount' => $this->formatCurrency($item->amount ?? 0),
                    'status' => $item->status ?? 'pending',
                    'ward' => $ward ? 'Ward ' . $ward->ward_no : 'N/A',
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    // ─── Owner Methods ───

    /**
     * Get total owners from MIS
     */
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
            // Column doesn't exist
        }

        return 0;
    }

    // ─── Activity Methods ───

    /**
     * Get recent activities from all tables
     */
    private function getRecentActivities($corporationId)
    {
        $activities = [];
        $tables = [
            'mis_' . $corporationId,
            'water_tax_' . $corporationId,
            'ugd_tax_' . $corporationId,
            'professional_tax_' . $corporationId,
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $columns = Schema::getColumnListing($table);
                $tableType = str_replace('_' . $corporationId, '', $table);

                $typeLabels = [
                    'mis' => 'Assessment',
                    'water_tax' => 'Water Tax',
                    'ugd_tax' => 'UGD Tax',
                    'professional_tax' => 'Professional Tax',
                ];

                $typeLabel = $typeLabels[$tableType] ?? ucfirst($tableType);

                if (in_array('created_at', $columns)) {
                    $recentItems = DB::table($table)
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();

                    foreach ($recentItems as $item) {
                        $itemNo = $item->assessment_no ?? $typeLabel . str_pad($item->id, 6, '0', STR_PAD_LEFT);
                        $ownerName = $item->owner_name ?? 'N/A';

                        $activities[] = [
                            'icon' => 'check2',
                            'color' => '#10b981',
                            'text' => '<strong>' . $typeLabel . '</strong> ' . $itemNo . ' created for ' . $ownerName,
                            'time' => $this->getTimeAgo($item->created_at),
                        ];
                    }
                }

                if (in_array('paid_at', $columns) && in_array('status', $columns)) {
                    $recentPayments = DB::table($table)
                        ->where('status', 'paid')
                        ->whereNotNull('paid_at')
                        ->orderBy('paid_at', 'desc')
                        ->limit(2)
                        ->get();

                    foreach ($recentPayments as $payment) {
                        $itemNo = $payment->assessment_no ?? $typeLabel . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                        $amount = $payment->amount ?? 0;

                        $activities[] = [
                            'icon' => 'arrow-repeat',
                            'color' => '#8b5cf6',
                            'text' => 'Payment received for <strong>' . $typeLabel . '</strong> ' . $itemNo . ' - ' . $this->formatCurrency($amount),
                            'time' => $this->getTimeAgo($payment->paid_at),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // Sort by time and take latest 7
        usort($activities, function ($a, $b) {
            $timeA = $this->parseTimeAgo($a['time']);
            $timeB = $this->parseTimeAgo($b['time']);
            return $timeB - $timeA;
        });

        return array_slice($activities, 0, 7);
    }

    /**
     * Parse time ago to timestamp for sorting
     */
    private function parseTimeAgo($timeString)
    {
        if (strpos($timeString, 'seconds ago') !== false) {
            $seconds = (int) filter_var($timeString, FILTER_SANITIZE_NUMBER_INT);
            return now()->subSeconds($seconds)->timestamp;
        } elseif (strpos($timeString, 'minutes ago') !== false) {
            $minutes = (int) filter_var($timeString, FILTER_SANITIZE_NUMBER_INT);
            return now()->subMinutes($minutes)->timestamp;
        } elseif (strpos($timeString, 'hours ago') !== false) {
            $hours = (int) filter_var($timeString, FILTER_SANITIZE_NUMBER_INT);
            return now()->subHours($hours)->timestamp;
        } elseif (strpos($timeString, 'days ago') !== false) {
            $days = (int) filter_var($timeString, FILTER_SANITIZE_NUMBER_INT);
            return now()->subDays($days)->timestamp;
        }
        return strtotime($timeString) ?: 0;
    }

    /**
     * Get time ago string
     */
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

    /**
     * Get corporation tables
     */
    private function getCorporationTables($corporationId)
    {
        return [
            'mis_' . $corporationId,
            'water_tax_' . $corporationId,
            'ugd_tax_' . $corporationId,
            'professional_tax_' . $corporationId,
        ];
    }

    /**
     * Format currency in Indian Rupee format
     */
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
}
