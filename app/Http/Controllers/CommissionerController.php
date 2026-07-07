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
    public function dashboard()
    {
        $user = auth()->user();
        $corporation = Corporation::with(['zones.wards'])->find($user->corporation_id);

        if (!$corporation) {
            return view('main.Commissioner.dashboard', [
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
                'getAllwardBoundary' => [],
                'waterTaxData' => [],
                'ugdData' => [],
                'professionalTaxData' => [],
            ]);
        }

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
        $connectedAssessments = $this->getConnectedAssessments($corporation->id, $allWardIds);

        // ─── Collection Statistics ───
        $totalcredits = $this->getTotalCredits($corporation->id);
        $halfyearbalance = $this->getHalfYearBalance($corporation->id);
        $yearCollection = $this->getYearCollection($corporation->id);
        $totalCollection = $this->getTotalCollection($corporation->id);

        // ─── Half Year Tax Totals ───
        $misHalfYearTax = $this->getMisHalfYearTax($corporation->id);
        $waterTaxHalfYearTax = $this->getWaterTaxHalfYearTax($corporation->id);
        $ugdHalfYearTax = $this->getUgdHalfYearTax($corporation->id);
        $professionalTaxHalfYearTax = $this->getProfessionalTaxHalfYearTax($corporation->id);
        $totalHalfYearTax = $this->getHalfYearTaxTotal($corporation->id);

        // ─── Tax-wise Collection ───
        $waterTaxCollection = $this->getWaterTaxCollection($corporation->id);
        $ugdCollection = $this->getUgdCollection($corporation->id);
        $professionalTaxCollection = $this->getProfessionalTaxCollection($corporation->id);
        $misCollection = $this->getMisCollection($corporation->id);
        $getAllwardBoundary = $this->getAllwardBoundary($corporation->id);

        // ─── Assessment Status ───
        $activeAssessments = $this->getActiveAssessments($corporation->id, $allWardIds);
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
            'total_half_year_tax' => $totalHalfYearTax,
        ];

        // ─── Tax Breakdown ───
        $taxBreakdown = [
            'mis' => [
                'count' => $misCount,
                'collection' => $misCollection,
                'half_year_tax' => $misHalfYearTax,
                'table' => 'mis_' . $corporation->id,
            ],
            'water_tax' => [
                'count' => $waterTaxCount,
                'collection' => $waterTaxCollection,
                'half_year_tax' => $waterTaxHalfYearTax,
                'table' => 'water_tax_' . $corporation->id,
            ],
            'ugd' => [
                'count' => $ugdCount,
                'collection' => $ugdCollection,
                'half_year_tax' => $ugdHalfYearTax,
                'table' => 'ugd_tax_' . $corporation->id,
            ],
            'professional_tax' => [
                'count' => $professionalTaxCount,
                'collection' => $professionalTaxCollection,
                'half_year_tax' => $professionalTaxHalfYearTax,
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

            $zoneWaterTax = $this->getWaterTaxByWards($corporation->id, $wardIds);
            $zoneUgd = $this->getUgdByWards($corporation->id, $wardIds);
            $zoneProfessionalTax = $this->getProfessionalTaxByWards($corporation->id, $wardIds);

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
                'collection' => $this->formatCurrency($collection),
                'pending' => $pending,
                'water_tax' => $zoneWaterTax,
                'ugd' => $zoneUgd,
                'professional_tax' => $zoneProfessionalTax,
                'officer' => $officer ? $officer->name : 'Not Assigned',
            ];
        });

        // ─── Zone-wise Collection Performance ───
        $performanceZones = $zones->map(function ($zone) use ($corporation) {
            $wardIds = $zone->wards->pluck('id')->toArray();

            $totalHalfYearTax = $this->getTotalHalfYearTaxByWards($corporation->id, $wardIds);
            $collected = $this->getCollectedByWards($corporation->id, $wardIds);
            $pending = $totalHalfYearTax - $collected;

            $target = $zone->target ?? $totalHalfYearTax;
            $achievement = $target > 0 ? round(($collected / $target) * 100) : 0;

            return [
                'name' => $zone->zone_name,
                'target' => $this->formatCurrency($target),
                'collected' => $this->formatCurrency($collected),
                'pending' => $this->formatCurrency(max(0, $pending)),
                'achievement' => min(100, $achievement),
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

        // ─── Building Data ───
        $buildingData = $this->getBuildingData($allWardIds, 10);
        $assessmentData = $this->getAssessmentData($corporation->id, 10);

        // ─── Tax Data Tables ───
        $waterTaxData = $this->getWaterTaxData($corporation->id, 5);
        $ugdData = $this->getUgdData($corporation->id, 5);
        $professionalTaxData = $this->getProfessionalTaxData($corporation->id, 5);

        // ─── Activities ───
        $activities = $this->getRecentActivities($corporation->id);

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
            'getAllwardBoundary'
        ));
    }

    // ─── Half Year Tax Methods ───

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
                } elseif (Schema::hasColumn($table, 'amount')) {
                    $total += DB::table($table)->sum('amount');
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
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)->sum('half_year_tax');
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
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)->sum('half_year_tax');
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

    // ─── Zone Performance Methods ───

    private function getTotalHalfYearTaxByWards($corporationId, $wardIds)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)
                    ->whereIn('ward_id', $wardIds)
                    ->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    private function getCollectedByWards($corporationId, $wardIds)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_id')) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status') && Schema::hasColumn($table, 'half_year_tax')) {
                return DB::table($table)
                    ->whereIn('ward_id', $wardIds)
                    ->where('status', 'paid')
                    ->sum('half_year_tax');
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    // ─── Building Methods ───

    private function getTotalBuildings($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $tables = $this->getWardPolygonTables($wardId);
            if (Schema::hasTable($tables[0])) {
                $total += DB::table($tables[0])->count();
            }
        }
        return $total;
    }

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
                        // Skip
                    }
                }
            }
        }
        return $total;
    }

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
                        // Skip
                    }
                }
            }
        }

        return $buildings;
    }

    private function getWardPolygonTables($wardId)
    {
        return [
            'polygons_' . $wardId,
            'polygon_data_' . $wardId,
        ];
    }

    // ─── Assessment Methods ───

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
            // Skip
        }

        return $assessments;
    }

    private function getActiveAssessments($corporationId)
    {
        $misTable = 'mis_' . $corporationId;
        if (!Schema::hasTable($misTable)) {
            return 0;
        }
        return DB::table($misTable)->count();
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
                    // Skip
                }
            }
        }
        return $total;
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
                    ->where('balance', '>', 1000)
                    ->count();
            }
        } catch (\Exception $e) {
            // Skip
        }
        return 0;
    }

    private function getPaidAssessments($corporationId)
    {
        $table = 'mis_' . $corporationId;
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            if (Schema::hasColumn($table, 'status')) {
                return DB::table($table)
                    ->where('status', 'paid')
                    ->count();
            }
        } catch (\Exception $e) {
            // Skip
        }
        return 0;
    }

    // ─── Survey Methods ───

    private function getSurveyedAssessments($wardIds)
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table)) {
                try {
                    $total += DB::table($table)->count();
                } catch (\Exception $e) {
                    // Skip
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
                    // Skip
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
                    // Skip
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

        $gisIds = [];
        try {
            if (Schema::hasColumn($misTable, 'gis_id')) {
                $gisIds = DB::table($misTable)->pluck('gis_id')->filter()->toArray();
            }
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($gisIds)) {
            return 0;
        }

        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'gis_id')) {
                try {
                    $total += DB::table($table)
                        ->whereIn('gis_id', $gisIds)
                        ->count();
                } catch (\Exception $e) {
                    // Skip
                }
            }
        }
        return $total;
    }

    // ─── Tax Type Count Methods ───

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

    private function getTotalCredits($corporationId)
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

    private function getHalfYearBalance($corporationId)
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
            return 0;
        }
        return 0;
    }

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
            return 0;
        }
        return 0;
    }

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
            return 0;
        }
        return 0;
    }

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
            return 0;
        }
        return 0;
    }

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

    // ─── Owner Methods ───

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

    // ─── Tax Data Methods ───

    private function getWaterTaxData($corporationId, $limit = 5)
    {
        $data = [];
        $table = 'water_tax_' . $corporationId;

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $results = DB::table($table)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gis_id)) ? 'paid' : 'pending';

                $data[] = [
                    'no' => $item->assessment_no ?? 'WT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($item->half_year_tax ?? 0),
                    'status' => $status,
                    'gis_id' => $item->gis_id ?? null,
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
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gis_id)) ? 'paid' : 'pending';

                $data[] = [
                    'no' => $item->assessment_no ?? 'UGD' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($item->half_year_tax ?? 0),
                    'status' => $status,
                    'gis_id' => $item->gis_id ?? null,
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
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($results as $item) {
                $status = (!empty($item->gis_id)) ? 'paid' : 'pending';

                $data[] = [
                    'no' => $item->assessment_no ?? 'PT' . str_pad($item->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $this->formatCurrency($item->half_year_tax ?? 0),
                    'status' => $status,
                    'gis_id' => $item->gis_id ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Skip if error
        }

        return $data;
    }

    // ─── Activity Methods ───

    private function getRecentActivities($corporationId)
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
                    ->orderBy('created_at', 'desc')
                    ->limit(3)
                    ->get();

                foreach ($recentItems as $item) {
                    $itemNo = $item->assessment_no ?? $typeLabel . str_pad($item->id, 6, '0', STR_PAD_LEFT);
                    $ownerName = $item->owner_name ?? 'N/A';

                    $statusText = '';
                    if (!empty($item->gis_id)) {
                        $statusText = '✓ Completed';
                    } else {
                        $statusText = '⏳ Pending';
                    }

                    $activities[] = [
                        'icon' => $icon,
                        'color' => $color,
                        'text' => '<strong>' . $typeLabel . '</strong> ' . $itemNo . ' - ' . $ownerName . ' (' . $statusText . ')',
                        'time' => $this->getTimeAgo($item->created_at ?? $item->updated_at ?? now()),
                    ];
                }

                $recentUpdates = DB::table($table)
                    ->whereNotNull('updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->limit(2)
                    ->get();

                foreach ($recentUpdates as $item) {
                    $itemNo = $item->assessment_no ?? $typeLabel . str_pad($item->id, 6, '0', STR_PAD_LEFT);

                    $exists = false;
                    foreach ($activities as $activity) {
                        if (strpos($activity['text'], $itemNo) !== false && strpos($activity['text'], 'updated') !== false) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists && isset($item->updated_at)) {
                        $activities[] = [
                            'icon' => 'arrow-repeat',
                            'color' => '#8b5cf6',
                            'text' => '<strong>' . $typeLabel . '</strong> ' . $itemNo . ' was updated',
                            'time' => $this->getTimeAgo($item->updated_at),
                        ];
                    }
                }

            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // Get entries from point_data tables
        try {
            $wardIds = $this->getWardIds($corporationId);
            foreach ($wardIds as $wardId) {
                $table = 'point_data_' . $wardId;
                if (Schema::hasTable($table)) {
                    $recentPoints = DB::table($table)
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();

                    foreach ($recentPoints as $point) {
                        $activities[] = [
                            'icon' => 'pin-map',
                            'color' => '#e11d48',
                            'text' => '<strong>Survey Entry</strong> - Building ' . ($point->building_no ?? 'N/A') . ' surveyed in Ward ' . $wardId,
                            'time' => $this->getTimeAgo($point->created_at ?? now()),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Skip
        }

        // Sort by time and take latest 10
        usort($activities, function ($a, $b) {
            $timeA = $this->parseTimeAgo($a['time']);
            $timeB = $this->parseTimeAgo($b['time']);
            return $timeB - $timeA;
        });

        return array_slice($activities, 0, 10);
    }

    private function getWardIds($corporationId)
    {
        $zones = Zone::where('corp_id', $corporationId)->get();
        $wardIds = [];
        foreach ($zones as $zone) {
            $wards = Ward::where('zone_id', $zone->id)->get();
            foreach ($wards as $ward) {
                $wardIds[] = $ward->id;
            }
        }
        return $wardIds;
    }

    // ─── Helper Methods ───

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

    private function getAllwardBoundary($corporationId)
    {
        $boundaries = [];
        try {
            $zones = Zone::where('corp_id', $corporationId)->get();
            foreach ($zones as $zone) {
                $wards = Ward::where('zone_id', $zone->id)->get();
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
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return [];
        }
        return $boundaries;
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
            'mis' => ['count' => 0, 'collection' => 0, 'half_year_tax' => 0, 'table' => ''],
            'water_tax' => ['count' => 0, 'collection' => 0, 'half_year_tax' => 0, 'table' => ''],
            'ugd' => ['count' => 0, 'collection' => 0, 'half_year_tax' => 0, 'table' => ''],
            'professional_tax' => ['count' => 0, 'collection' => 0, 'half_year_tax' => 0, 'table' => ''],
        ];
    }

    // ─── Show Map Method ───

    public function showMap($id)
    {
        $user = Auth::user();
        $wardId = $id;
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
