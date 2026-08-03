<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\Zone;
use App\Models\Ward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CommissionerController extends Controller
{
    /**
     * Central config for the 4 tax-type tables.
     * Every tax-type-specific method that used to be duplicated 4x
     * (water_tax / ugd / professional_tax / mis) now reads from here.
     */
    private array $taxTypes = [
        'mis' => [
            'table_prefix'   => 'mis_',
            'tax_column'     => 'half_year_tax',
            'balance_column' => 'balance',
            'number_field'   => 'assessment',
            'prefix'         => 'AST',
            'label'          => 'Assessment',
            'color'          => '#0f6b47',
            'icon'           => 'clipboard-data',
        ],
        'water_tax' => [
            'table_prefix'   => 'water_tax_',
            'tax_column'     => 'slab_rate',
            'balance_column' => 'balance',
            'number_field'   => 'watertax_no',
            'prefix'         => 'WT',
            'label'          => 'Water Tax',
            'color'          => '#1d4ed8',
            'icon'           => 'droplet',
        ],
        'ugd' => [
            'table_prefix'   => 'ugd_tax_',
            'tax_column'     => 'ugd_tax_amount',
            'balance_column' => 'balance',
            'number_field'   => 'ugd_no',
            'prefix'         => 'UGD',
            'label'          => 'UGD Tax',
            'color'          => '#a9741a',
            'icon'           => 'pipe',
        ],
        'professional_tax' => [
            'table_prefix'   => 'professional_tax_',
            'tax_column'     => 'half_year_tax',
            'balance_column' => 'balance',
            'number_field'   => 'pt_number',
            'prefix'         => 'PT',
            'label'          => 'Professional Tax',
            'color'          => '#5b21b6',
            'icon'           => 'briefcase',
        ],
    ];

    // ════════════════════════════════════════════════════════════════
    // ACCESS CONTROL (was: getAccessibleWardIds + getAccessibleWardNos,
    // near-duplicate role-branching logic)
    // ════════════════════════════════════════════════════════════════

    /**
     * Single source of truth for "which wards can this user see".
     * Returns Ward models (id + ward_no) so callers can pluck whichever they need.
     */
    private function getAccessibleWardsQuery($corporationId = null)
    {
        $user = Auth::user();
        $corporationId = $corporationId ?? $user->corporation_id;

        if ($user->isCommissioner() || $user->isDC()) {
            $zoneIds = Zone::where('corp_id', $corporationId)->pluck('id');
            return Ward::whereIn('zone_id', $zoneIds)->get();
        }

        if ($user->isAC() || $user->isARO()) {
            if (!$user->zone_id) {
                return collect();
            }
            return Ward::where('zone_id', $user->zone_id)->get();
        }

        if ($user->isBC()) {
            if (!$user->ward_id) {
                return collect();
            }
            return Ward::where('id', $user->ward_id)->get();
        }

        return collect();
    }

    private function getAccessibleWardIds()
    {
        return $this->getAccessibleWardsQuery()->pluck('id')->toArray();
    }

    private function getAccessibleWardNos($corporationId)
    {
        return $this->getAccessibleWardsQuery($corporationId)->pluck('ward_no')->toArray();
    }

    /**
     * Was copy-pasted in ~8 API methods as:
     *   $accessibleWardIds = $this->getAccessibleWardIds();
     *   if (!in_array($wardId, $accessibleWardIds)) return response()->json(['error'=>'Unauthorized'],403);
     * Now: if ($resp = $this->denyIfNoWardAccess($wardId)) return $resp;
     */
    private function denyIfNoWardAccess($wardId): ?JsonResponse
    {
        if ($wardId === null) {
            return null;
        }
        if (!in_array($wardId, $this->getAccessibleWardIds())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // GENERIC SCHEMA-SAFE DB HELPERS
    // (replace get{Mis,WaterTax,Ugd,ProfessionalTax}{Count,Balance,HalfYearTax,ByWards})
    // ════════════════════════════════════════════════════════════════

    private function taxTable(string $type, $corporationId): string
    {
        return $this->taxTypes[$type]['table_prefix'] . $corporationId;
    }

    private function countTable(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }
        try {
            return DB::table($table)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function countByWardNos(string $table, array $wardNos): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no')) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function sumColumn(string $table, ?string $column)
    {
        if (!$column || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0;
        }
        try {
            return DB::table($table)->sum($column);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function sumColumnByWardNos(string $table, array $wardNos, ?string $column)
    {
        if (!$column || !Schema::hasTable($table) || !Schema::hasColumn($table, 'ward_no') || !Schema::hasColumn($table, $column)) {
            return 0;
        }
        try {
            return DB::table($table)->whereIn('ward_no', $wardNos)->sum($column);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /** Building count for a set of ward ids (was getTotalBuildings + getBuildingsByWards). */
    private function getBuildingsByWards(array $wardIds): int
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $total += $this->countTable("polygons_{$wardId}");
        }
        return $total;
    }

    /** Surveyed points for a set of ward ids (was getSurveyedAssessments + getSurveyedByWards). */
    private function getSurveyedByWards(array $wardIds): int
    {
        $total = 0;
        foreach ($wardIds as $wardId) {
            $total += $this->countTable("point_data_{$wardId}");
        }
        return $total;
    }

    /** Connected (points whose gisid appears in mis) for a set of wards
     *  (was getConnectedAssessments + getConnectedByWards). */
    private function getConnectedByWards($corporationId, array $wardIds): int
    {
        $misTable = $this->taxTable('mis', $corporationId);
        if (!Schema::hasTable($misTable) || !Schema::hasColumn($misTable, 'gisid')) {
            return 0;
        }

        try {
            $gisids = DB::table($misTable)->pluck('gisid')->filter()->toArray();
        } catch (\Exception $e) {
            return 0;
        }

        if (empty($gisids)) {
            return 0;
        }

        $total = 0;
        foreach ($wardIds as $wardId) {
            $table = "point_data_{$wardId}";
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'gisid')) {
                try {
                    $total += DB::table($table)->whereIn('gisid', $gisids)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    // ── Tax-type generic accessors (replace 4x duplicated methods each) ──

    private function getTaxCount(string $type, $corporationId): int
    {
        return $this->countTable($this->taxTable($type, $corporationId));
    }

    private function getTaxCountByWards(string $type, $corporationId, array $wardNos): int
    {
        return $this->countByWardNos($this->taxTable($type, $corporationId), $wardNos);
    }

    private function getTaxHalfYearTotal(string $type, $corporationId)
    {
        $cfg = $this->taxTypes[$type];
        return $this->sumColumn($this->taxTable($type, $corporationId), $cfg['tax_column']);
    }

    private function getTaxHalfYearTotalByWards(string $type, $corporationId, array $wardNos)
    {
        $cfg = $this->taxTypes[$type];
        return $this->sumColumnByWardNos($this->taxTable($type, $corporationId), $wardNos, $cfg['tax_column']);
    }

    private function getTaxBalance(string $type, $corporationId)
    {
        $cfg = $this->taxTypes[$type];
        return $this->sumColumn($this->taxTable($type, $corporationId), $cfg['balance_column']);
    }

    private function getTaxBalanceByWards(string $type, $corporationId, array $wardNos)
    {
        $cfg = $this->taxTypes[$type];
        return $this->sumColumnByWardNos($this->taxTable($type, $corporationId), $wardNos, $cfg['balance_column']);
    }

    /** Sum of half-year-tax across all 4 tax tables (was getHalfYearTaxTotal). */
    private function getAllTaxHalfYearTotal($corporationId)
    {
        $total = 0;
        foreach (array_keys($this->taxTypes) as $type) {
            $total += $this->getTaxHalfYearTotal($type, $corporationId);
        }
        return $total;
    }

    /** Recent rows for a tax table, generic (was get{WaterTax,Ugd,ProfessionalTax}Data). */
    private function getTaxData(string $type, $corporationId, int $limit = 5): array
    {
        $cfg = $this->taxTypes[$type];
        $table = $this->taxTable($type, $corporationId);
        $data = [];

        if (!Schema::hasTable($table)) {
            return $data;
        }

        try {
            $results = DB::table($table)->orderBy('id', 'desc')->limit($limit)->get();
            $numberField = $cfg['number_field'];

            foreach ($results as $item) {
                $status = (!empty($item->gisid)) ? 'paid' : 'pending';
                $amount = $item->{$cfg['tax_column']} ?? 0;

                $data[] = [
                    'no'     => $item->$numberField ?? ($cfg['prefix'] . str_pad($item->id, 6, '0', STR_PAD_LEFT)),
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

    // ════════════════════════════════════════════════════════════════
    // DASHBOARD
    // ════════════════════════════════════════════════════════════════

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

        // ─── Accessible zones for this role ───
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
                    $zone = Zone::with(['wards' => function ($query) use ($user) {
                        $query->where('id', $user->ward_id);
                    }])->find($ward->zone_id);
                    if ($zone) {
                        $zones = collect([$zone]);
                    }
                }
            }
        }

        $allWardIds = $zones->flatMap(fn($zone) => $zone->wards->pluck('id'))->toArray();

        // ─── Hierarchy Statistics ───
        $totalZones = $zones->count();
        $totalWards = count($allWardIds);
        $totalBuildings = $this->getBuildingsByWards($allWardIds);
        $totalAssessments = $this->getTaxCount('mis', $corporation->id);

        // ─── Tax Type Statistics ───
        $misCount = $totalAssessments;
        $waterTaxCount = $this->getTaxCount('water_tax', $corporation->id);
        $ugdCount = $this->getTaxCount('ugd', $corporation->id);
        $professionalTaxCount = $this->getTaxCount('professional_tax', $corporation->id);

        // ─── Survey & Connection Statistics ───
        $surveyedAssessments = $this->getSurveyedByWards($allWardIds);
        $connectedAssessments = $this->getConnectedByWards($corporation->id, $allWardIds);

        // ─── Half Year Tax Totals ───
        $misHalfYearTax = $this->getTaxHalfYearTotal('mis', $corporation->id);
        $waterTaxHalfYearTax = $this->getTaxHalfYearTotal('water_tax', $corporation->id);
        $ugdHalfYearTax = $this->getTaxHalfYearTotal('ugd', $corporation->id);
        $professionalTaxHalfYearTax = $this->getTaxHalfYearTotal('professional_tax', $corporation->id);
        $totalHalfYearTax = $this->getAllTaxHalfYearTotal($corporation->id);

        // ─── Balance Totals ───
        $misBalance = $this->getTaxBalance('mis', $corporation->id);
        $waterTaxBalance = $this->getTaxBalance('water_tax', $corporation->id);
        $ugdBalance = $this->getTaxBalance('ugd', $corporation->id);
        $professionalTaxBalance = $this->getTaxBalance('professional_tax', $corporation->id);
        $totalBalance = $misBalance + $waterTaxBalance + $ugdBalance + $professionalTaxBalance;

        $getAllwardBoundary = $this->getAllwardBoundary($corporation->id, $accessibleWardIds);

        // ─── Ward Variation Stats ───
        $wardVariationStats = $this->getWardVariationStats($corporation->id, $zones);

        // ─── Assessment Status ───
        $activeAssessments = $totalAssessments;
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
            'total_collection' => $totalBalance - $totalHalfYearTax,
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
                'table' => $this->taxTable('mis', $corporation->id),
            ],
            'water_tax' => [
                'count' => $waterTaxCount,
                'half_year_tax' => $waterTaxHalfYearTax,
                'balance' => $waterTaxBalance,
                'table' => $this->taxTable('water_tax', $corporation->id),
            ],
            'ugd' => [
                'count' => $ugdCount,
                'half_year_tax' => $ugdHalfYearTax,
                'balance' => $ugdBalance,
                'table' => $this->taxTable('ugd', $corporation->id),
            ],
            'professional_tax' => [
                'count' => $professionalTaxCount,
                'half_year_tax' => $professionalTaxHalfYearTax,
                'balance' => $professionalTaxBalance,
                'table' => $this->taxTable('professional_tax', $corporation->id),
            ],
        ];

        // ─── Zone Data with Counts ───
        $zoneData = $zones->map(function ($zone) use ($corporation) {
            $wards = $zone->wards;
            $wardIds = $wards->pluck('id')->toArray();
            $wardNos = $wards->pluck('ward_no')->toArray();

            $buildingsCount = $this->getBuildingsByWards($wardIds);
            $assessmentsCount = $this->getTaxCountByWards('mis', $corporation->id, $wardNos);
            $balance = $this->getTaxBalanceByWards('mis', $corporation->id, $wardNos);
            $surveyed = $this->getSurveyedByWards($wardIds);
            $connected = $this->getConnectedByWards($corporation->id, $wardIds);

            $zoneWaterTax = $this->getTaxCountByWards('water_tax', $corporation->id, $wardNos);
            $zoneUgd = $this->getTaxCountByWards('ugd', $corporation->id, $wardNos);
            $zoneProfessionalTax = $this->getTaxCountByWards('professional_tax', $corporation->id, $wardNos);

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

            $totalHalfYearTax = $this->getTaxHalfYearTotalByWards('mis', $corporation->id, $wardNos);
            $balance = $this->getTaxBalanceByWards('mis', $corporation->id, $wardNos);
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
                $assessmentsCount = $this->getTaxCountByWards('mis', $corporation->id, $wardNos);
                $balance = $this->getTaxBalanceByWards('mis', $corporation->id, $wardNos);
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

        // ─── Building / Assessment Data ───
        $buildingData = $this->getBuildingData($allWardIds, 10);
        $assessmentData = $this->getAssessmentData($corporation->id, 10);

        // ─── Tax Data Tables ───
        $waterTaxData = $this->getTaxData('water_tax', $corporation->id, 5);
        $ugdData = $this->getTaxData('ugd', $corporation->id, 5);
        $professionalTaxData = $this->getTaxData('professional_tax', $corporation->id, 5);

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
                $zoneIds = Zone::where('corp_id', $corporationId)->pluck('id')->toArray();
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
            Log::error($e->getMessage());
            return [];
        }
        return $boundaries;
    }

    // ════════════════════════════════════════════════════════════════
    // MAP VIEW METHODS
    // ════════════════════════════════════════════════════════════════

    public function map()
    {
        $accessibleWardIds = $this->getAccessibleWardIds();

        if (empty($accessibleWardIds)) {
            return redirect()->back()->with('error', 'No wards accessible for your role.');
        }

        return redirect()->route('commissioner.ward.showmap', ['id' => $accessibleWardIds[0]]);
    }

    public function showMap($id)
    {
        $wardId = $id;

        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            abort(403, 'You do not have access to this ward.');
        }

        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName = "polygons_{$wardId}";
        $linesTableName = "lines_{$wardId}";
        $pointsTableName = "points_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";

        $misTableName = $this->taxTable('mis', $corp);
        $waterTaxTableName = $this->taxTable('water_tax', $corp);
        $ugdtable = $this->taxTable('ugd', $corp);
        $prefessionaltax = $this->taxTable('professional_tax', $corp);

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

        // ─── Analytics ───
        $boundary = $this->getAllwardBoundary($corp,$wardId);
        $analytics = $this->buildWardAnalytics($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingData = $this->getBuildingsWithUsageColors($wardId);
        $availableUsages = array_keys($buildingData['usage_counts']);
        sort($availableUsages);
        $areaStats = $this->getAreaVariationStats($wardId, $buildingData['buildings']);

        // Navigation among accessible wards
        $accessibleWardIds = $this->getAccessibleWardIds();
        $accessibleWards = Ward::whereIn('id', $accessibleWardIds)->orderBy('ward_no')->get();
        $currentIndex = array_search($wardId, $accessibleWardIds);
        $nextWardId = ($currentIndex !== false && isset($accessibleWardIds[$currentIndex + 1]))
            ? $accessibleWardIds[$currentIndex + 1]
            : null;

        foreach ($polygons as $polygon) {
            $groundSqfeet = (float) ($polygon->sqfeet ?? 0);
            $polyData = $polygonDatas->firstWhere('gisid', $polygon->gisid);
            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);
                $totalSqfeet = ($numberFloor > 0 ? $numberFloor : 1) * $groundSqfeet;
                if ($basement > 0) {
                    $totalSqfeet += ($groundSqfeet * $basement);
                }
                $polygon->sqfeet = $totalSqfeet;
            }
        }
return response()->json($boundary);
        return view('excecutive.mapview', compact(
            'ward',
            'polygons',
            'points',
            'lines',
            'polygonDatas',
            'pointDatas',
            'boundary',
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
        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            return $resp;
        }

        $ward = Ward::findOrFail($wardId);

        return response()->json([
            'ward' => $ward,
            'polygons' => DB::table("polygons_{$wardId}")->get(),
            'lines' => DB::table("lines_{$wardId}")->get(),
            'points' => DB::table("points_{$wardId}")->get(),
            'polygonDatas' => DB::table("polygon_data_{$wardId}")->get(),
            'pointDatas' => DB::table("point_data_{$wardId}")->get(),
        ]);
    }

    public function getInfrastructureData($wardId)
    {
        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            return $resp;
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
        $wardId = $request->ward_id ?? null;

        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            return $resp;
        }

        $request->validate([
            'gisid' => 'required',
            'coordinates' => 'required',
            'sqfeet' => 'nullable|numeric',
            'ward_id' => 'nullable|integer'
        ]);

        try {
            $gisid = $request->gisid;
            $wardId = $request->ward_id;

            if (!$wardId) {
                // Find the ward by searching through polygon tables
                foreach ($this->getAccessibleWardIds() as $wid) {
                    $table = "polygons_{$wid}";
                    if (Schema::hasTable($table) && DB::table($table)->where('gisid', $gisid)->exists()) {
                        $wardId = $wid;
                        break;
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

            return response()->json([
                'success' => true,
                'message' => 'Polygon updated successfully',
                'data' => [
                    'polygons' => DB::table($table)->get()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function splitPolygon(Request $request)
    {
        if ($resp = $this->denyIfNoWardAccess($request->ward_id ?? null)) {
            return $resp;
        }

        // This is a placeholder - implement polygon splitting logic
        return response()->json([
            'success' => true,
            'message' => 'Polygon split functionality - implement your logic here'
        ]);
    }

    public function saveFeature(Request $request)
    {
        if ($resp = $this->denyIfNoWardAccess($request->ward_id ?? null)) {
            return $resp;
        }

        // This is a placeholder - implement feature saving logic
        return response()->json([
            'success' => true,
            'message' => 'Feature saved - implement your logic here'
        ]);
    }

    public function deleteFeature(Request $request)
    {
        if ($resp = $this->denyIfNoWardAccess($request->ward_id ?? null)) {
            return $resp;
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

        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            return $resp;
        }

        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corpId = $zone->corp_id;

        $pointTable = "point_data_{$wardId}";
        $misTable = $this->taxTable('mis', $corpId);
        $waterTaxTable = $this->taxTable('water_tax', $corpId);
        $ugdTaxTable = $this->taxTable('ugd', $corpId);
        $professionalTaxTable = $this->taxTable('professional_tax', $corpId);

        if (!Schema::hasTable($pointTable)) {
            return response()->json([
                'status' => false,
                'message' => 'Point table not found.'
            ], 404);
        }

        $points = DB::table($pointTable)->where('point_gisid', $gisid)->get();

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
                $mis = DB::table($misTable)->where('assessment', $point->assessment)->first();
            }

            $waterTax = null;
            if (Schema::hasTable($waterTaxTable) && !empty($point->assessment)) {
                $waterTax = DB::table($waterTaxTable)->where('assessment', $point->assessment)->first();
            }

            $ugdTax = null;
            if (Schema::hasTable($ugdTaxTable)) {
                $ugdTax = DB::table($ugdTaxTable)->where('gisid', $point->point_gisid)->first();
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

        if ($resp = $this->denyIfNoWardAccess($wardId)) {
            return $resp;
        }

        $pointTable = "point_data_{$wardId}";
        $point = DB::table($pointTable)->where('id', $id)->first();

        if (!$point) {
            return response()->json(['message' => 'Point data not found.'], 404);
        }

        $user = Auth::user();
        $qcName = ($user->name ?? 'Unknown User') . ' (' . ($user->role ?? 'Unknown') . ')';

        DB::table($pointTable)
            ->where('id', $id)
            ->update([
                'qcusage'     => $request->qcusage,
                'qcsqfeet'    => $request->qcsqfeet,
                'qc_remarks'  => $request->qc_remarks,
                'qc_name'     => $qcName,
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success'    => true,
            'message'    => 'QC data updated successfully.',
            'point_data' => DB::table($pointTable)->where('id', $id)->first(),
            'qc_by'      => $qcName,
        ]);
    }

    // ─── WARD ANALYTICS HELPERS ───

    private function getWardVariationStats($corporationId, $zones)
    {
        $wardStats = [];
        $misTable = $this->taxTable('mis', $corporationId);

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

    /**
     * Shared "building area vs assessment area / usage mismatch" computation.
     * Used by both buildWardAnalytics (aggregate) and buildBuildingVariations (per-building).
     */
    private function computeBuildingComparison($polygon, $polygonDataByGisid, $pointDataByGisid, $misByAssessment): array
    {
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

        return [
            'gisid' => $gisid,
            'building_area' => $buildingArea,
            'assessment_area' => $assessmentArea,
            'assessment_count' => $assessmentCount,
            'usage_mismatch' => $hasUsageMismatch,
        ];
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
            $c = $this->computeBuildingComparison($polygon, $polygonDataByGisid, $pointDataByGisid, $misByAssessment);

            $totalBuildingArea += $c['building_area'];
            $totalAssessmentArea += $c['assessment_area'];

            if ($c['building_area'] > 0 && $c['assessment_area'] > 0) {
                $validBuildingsCount++;
                if (abs($c['building_area'] - $c['assessment_area']) > 1) {
                    $areaVariationCount++;
                }
                if ($c['usage_mismatch']) {
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
            $c = $this->computeBuildingComparison($polygon, $polygonDataByGisid, $pointDataByGisid, $misByAssessment);

            $areaVariation = $c['building_area'] - $c['assessment_area'];
            $variationPercentage = $c['building_area'] > 0
                ? round((abs($areaVariation) / $c['building_area']) * 100, 1) : 0;

            $result[$c['gisid']] = [
                'gisid' => $c['gisid'],
                'building_area' => round($c['building_area'], 2),
                'assessment_area' => round($c['assessment_area'], 2),
                'area_variation' => round($areaVariation, 2),
                'variation_percentage' => $variationPercentage,
                'area_status' => (abs($areaVariation) > 1) ? 'VARIATION' : 'MATCH',
                'usage_status' => $c['usage_mismatch'] ? 'VARIATION' : 'MATCH',
                'assessment_count' => $c['assessment_count'],
            ];
        }

        return $result;
    }

    private function getAreaVariationStats($wardId, $buildings)
    {
        $stats = ['min' => 0, 'max' => 0, 'avg' => 0, 'total' => 0, 'count' => 0];

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

        $usageKeywords = [
            'RESIDENTIAL'   => ['RESIDENT', 'DWELLING'],
            'COMMERCIAL'    => ['SHOP', 'RETAIL', 'COMMERCIAL'],
            'INDUSTRIAL'    => ['FACTORY', 'MANUFACT'],
            'INSTITUTIONAL' => ['SCHOOL', 'HOSPITAL', 'COLLEGE'],
            'GOVERNMENT'    => ['GOV', 'OFFICE', 'MUNICIPAL'],
            'VACANT'        => ['VACANT', 'EMPTY'],
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
                $rawUsage = strtoupper(trim($buildingData->building_usage));
                $usage = $rawUsage;
                foreach ($usageKeywords as $category => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (strpos($rawUsage, $keyword) !== false) {
                            $usage = $category;
                            break 2;
                        }
                    }
                }
            }

            $color = $usageColors[$usage] ?? $usageColors['OTHER'];
            $usageCounts[$usage] = ($usageCounts[$usage] ?? 0) + 1;

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

        foreach ($this->taxTypes as $type => $cfg) {
            $table = $this->taxTable($type, $corporationId);
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $recentItems = DB::table($table)->orderBy('id', 'desc')->limit(3)->get();
                $numberField = $cfg['number_field'];

                foreach ($recentItems as $item) {
                    $itemNo = $item->$numberField ?? $cfg['label'] . str_pad($item->id, 6, '0', STR_PAD_LEFT);
                    $ownerName = $item->owner_name ?? 'N/A';
                    $statusText = !empty($item->gisid) ? '✓ Completed' : '⏳ Pending';

                    $activities[] = [
                        'icon' => $cfg['icon'],
                        'color' => $cfg['color'],
                        'text' => '<strong>' . $cfg['label'] . '</strong> ' . $itemNo . ' - ' . $ownerName . ' (' . $statusText . ')',
                        'time' => $this->getTimeAgo(now()),
                    ];
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // Recent survey entries for accessible wards
        foreach ($accessibleWardIds as $wardId) {
            $table = 'point_data_' . $wardId;
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $recentPoints = DB::table($table)->orderBy('id', 'desc')->limit(2)->get();
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

        return array_slice($activities, 0, 10);
    }

    // ════════════════════════════════════════════════════════════════
    // REMAINING STAT HELPERS (not tax-type duplicated)
    // ════════════════════════════════════════════════════════════════

    private function getPaidAssessments($corporationId)
    {
        $table = $this->taxTable('mis', $corporationId);
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'balance')) {
            return 0;
        }
        try {
            return DB::table($table)->where('balance', '=', 0)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getOverdueAssessments($corporationId)
    {
        $table = $this->taxTable('mis', $corporationId);
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'balance')) {
            return 0;
        }
        try {
            return DB::table($table)->where('balance', '>', 0)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getNotInMis($corporationId, $wardIds)
    {
        $misTable = $this->taxTable('mis', $corporationId);
        if (!Schema::hasTable($misTable)) {
            return 0;
        }

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
                    $total += DB::table($table)->whereNotIn('assessment', $assessments)->count();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $total;
    }

    private function getTotalOwners($corporationId)
    {
        $table = $this->taxTable('mis', $corporationId);
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'owner_name')) {
            return 0;
        }
        try {
            $owners = DB::table($table)->pluck('owner_name')->filter()->toArray();
            return count(array_unique($owners));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAssessmentData($corporationId, $limit = 10)
    {
        $assessments = [];
        $table = $this->taxTable('mis', $corporationId);

        if (!Schema::hasTable($table)) {
            return $assessments;
        }

        try {
            $columns = Schema::getColumnListing($table);
            $select = ['id'];

            foreach (['assessment', 'owner_name', 'new_door_no', 'old_door_no', 'type', 'half_year_tax', 'balance', 'gisid', 'ward_no', 'road_name'] as $col) {
                if (in_array($col, $columns)) {
                    $select[] = $col;
                }
            }

            $results = DB::table($table)->select($select)->orderBy('id', 'desc')->limit($limit)->get();

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
            if ($count >= $limit) {
                break;
            }

            $table = "polygons_{$wardId}";
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $columns = Schema::getColumnListing($table);
                $select = array_values(array_intersect(['gisid', 'sqfeet', 'coordinates'], $columns));
                if (empty($select)) {
                    $select = ['id'];
                }

                $results = DB::table($table)->select($select)->limit($limit - $count)->get();

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

        return $buildings;
    }

    private function formatCurrency($amount)
    {
        if (!$amount) return '₹0';
        $amount = (int) $amount;
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
        return ['zones' => 0, 'wards' => 0, 'buildings' => 0, 'assessments' => 0, 'surveyed' => 0, 'connected' => 0];
    }

    private function getEmptyTaxBreakdown()
    {
        $empty = ['count' => 0, 'half_year_tax' => 0, 'balance' => 0, 'table' => ''];
        return [
            'mis' => $empty,
            'water_tax' => $empty,
            'ugd' => $empty,
            'professional_tax' => $empty,
        ];
    }
}
