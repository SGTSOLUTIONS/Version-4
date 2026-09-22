<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ward;
use App\Models\Zone;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VariationController extends Controller
{
    private function normalizeAssessment($value): string
{
    $value = (string) $value;
    $value = preg_replace('/\s+/', '', $value);
    $value = str_replace(['\\', '-', '.', '_'], '/', $value);
    $value = strtoupper(trim($value));

    $parts = explode('/', $value);
    $parts = array_map(function ($p) {
        return ctype_digit($p) ? (ltrim($p, '0') ?: '0') : $p;
    }, $parts);

    return implode('/', $parts);
}

    /**
     * Fetch MIS rows for a specific ward (tries string, then int, then PHP filter).
     */
    private function fetchMisData(string $misTableName, $wardNo)
    {
        // Try string match
        $misData = DB::table($misTableName)
            ->where('ward_no', (string) $wardNo)
            ->get();

        if ($misData->isEmpty()) {
            // Try integer match
            $misData = DB::table($misTableName)
                ->where('ward_no', (int) $wardNo)
                ->get();
        }

        if ($misData->isEmpty()) {
            // Fallback: full fetch + PHP filter (handles odd formatting like "057")
            $all = DB::table($misTableName)->get();
            $misData = $all->filter(function ($row) use ($wardNo) {
                return (string) ($row->ward_no ?? '') === (string) $wardNo;
            })->values();
        }

        return $misData;
    }

    /**
     * Fetch ALL MIS rows for the corp (no ward filter).
     * Used as fallback when a point_data assessment isn't in the ward-filtered MIS.
     */
    private function fetchAllMisData(string $misTableName)
    {
        return DB::table($misTableName)->get();
    }

    /**
     * Area Variation
     */
    public function areaVariation($wardId)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);

        $corp   = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName    = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName   = "point_data_{$wardId}";
        $misTableName         = "mis_{$corp}";

        $polygons     = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas   = DB::table($pointDataTableName)->get();

        // Ward-filtered MIS (primary) + all corp MIS (fallback)
        $misData    = $this->fetchMisData($misTableName, $wardNo);
        $allMisData = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingVariations(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData,
            $allMisData
        );

        return view('variation.area_variation', compact(
            'ward',
            'zone',
            'buildingVariations'
        ));
    }

    /**
     * Usage Variation
     */
    public function usageVariation($wardId)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);

        $corp   = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName    = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName   = "point_data_{$wardId}";
        $misTableName         = "mis_{$corp}";

        $polygons     = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas   = DB::table($pointDataTableName)->get();

        $misData    = $this->fetchMisData($misTableName, $wardNo);
        $allMisData = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingVariations(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData,
            $allMisData
        );

        return view('variation.usage_variation', compact(
            'ward',
            'zone',
            'buildingVariations'
        ));
    }

    /**
     * Build Area & Usage Variation (used by areaVariation + usageVariation)
     */
    private function buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData, $allMisData = null)
    {
        $polygonDataByGisid = collect($polygonDatas)->keyBy('gisid');

        // ─── PRIMARY MIS LOOKUP (ward-filtered) ───
        $misByAssessment = collect($misData)->keyBy(function ($item) {
            return $this->normalizeAssessment($item->assessment ?? '');
        });

        // ─── FALLBACK MIS LOOKUP (all corp MIS, no ward filter) ───
        $allMisByAssessment = collect($allMisData ?? $misData)->keyBy(function ($item) {
            return $this->normalizeAssessment($item->assessment ?? '');
        });

        // Group point data by GIS ID
        $pointDataByGisid = [];
        foreach ($pointDatas as $pd) {
            $pointDataByGisid[$pd->point_gisid][] = $pd;
        }

        $result = [];

        foreach ($polygons as $polygon) {

            $gisid         = $polygon->gisid;
            $polygonSqfeet = floatval($polygon->sqfeet ?? 0);

            $polyData = $polygonDataByGisid->get($gisid);

            // ─── BUILDING USAGE & AREA ───
            $buildingUsage = null;
            $buildingArea  = $polygonSqfeet;
            $numberFloor   = 1;
            $basement      = 0;
            $percentage    = 0;

            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement    = floatval($polyData->basement ?? 0);
                $percentage  = floatval(($polyData->percentage / 100) ?? 0);

                $buildingArea = ($numberFloor > 0 ? $numberFloor + $percentage : 1) * $polygonSqfeet;

                if ($basement > 0) {
                    $buildingArea += ($polygonSqfeet * $basement);
                }

                $buildingUsage = $polyData->building_usage ?? null;
            }

            // ─── ASSESSMENT DATA ───
            $assessmentArea      = 0;
            $assessmentCount     = 0;
            $assessmentUsage     = null;
            $allAssessmentUsages = [];
            $allAssessmentTypes  = [];
            $matchedCount        = 0;
            $mismatchedCount     = 0;

            $hasResidential  = false;
            $hasCommercial   = false;
            $commercialIsNew = false;

            if (isset($pointDataByGisid[$gisid])) {

                foreach ($pointDataByGisid[$gisid] as $pd) {

                    $assessmentCount++;

                    // ─── NORMALIZED MIS LOOKUP (ward first, then any ward) ───
                    $assessmentKey = $this->normalizeAssessment($pd->assessment ?? '');
                    $mis = $misByAssessment->get($assessmentKey);

                    if (!$mis) {
                        // Fallback: check MIS from other wards for this assessment
                        $mis = $allMisByAssessment->get($assessmentKey);
                    }

                    // ─── POINT AREA (qcsqfeet first, then MIS plot_area) ───
                    $pointArea = 0;

                    $qcArea = floatval($pd->qcsqfeet ?? 0);

                    if ($qcArea > 0) {
                        $pointArea = $qcArea;
                    } elseif ($mis && floatval($mis->plot_area ?? 0) > 0) {
                        $pointArea = floatval($mis->plot_area);
                    }

                    $assessmentArea += $pointArea;

                    // ─── POINT USAGE ───
                    $pointUsage      = $pd->qcusage ?? $pd->bill_usage ?? null;
                    $pointUsageUpper = $pointUsage ? strtoupper(trim($pointUsage)) : null;

                    // ─── POINT ASSESSMENT TYPE ───
                    $pointAssessmentType = strtoupper(trim($pd->assessment_type ?? ''));

                    if ($pointUsage) {
                        $allAssessmentUsages[] = $pointUsage;

                        if (!$assessmentUsage) {
                            $assessmentUsage = $pointUsage;
                        }

                        if ($pointUsageUpper === 'RESIDENTIAL') {
                            $hasResidential = true;
                        }
                        if ($pointUsageUpper === 'COMMERCIAL') {
                            $hasCommercial = true;

                            if ($pointAssessmentType === 'NEW') {
                                $commercialIsNew = true;
                            }
                        }
                    }

                    if ($pointAssessmentType) {
                        $allAssessmentTypes[] = $pointAssessmentType;
                    }

                    // ─── MATCH / MISMATCH PER POINT ───
                    if ($buildingUsage && $pointUsage) {

                        $buildingUsageUpper = strtoupper(trim($buildingUsage));
                        $isMatch = false;

                        if (str_contains($buildingUsageUpper, 'MIX')) {
                            if (in_array($pointUsageUpper, ['RESIDENTIAL', 'COMMERCIAL', 'MIXED'])) {
                                $isMatch = true;
                            }
                        } elseif ($buildingUsageUpper === 'RESIDENTIAL') {
                            $isMatch = ($pointUsageUpper === 'RESIDENTIAL');
                        } elseif (in_array($buildingUsageUpper, [
                            'COMMERCIAL', 'INDUSTRIAL', 'INSTITUTIONAL',
                            'GOVERNMENT', 'VACANT', 'OTHER',
                        ])) {
                            $isMatch = ($pointUsageUpper === 'COMMERCIAL');
                        }

                        if ($isMatch) {
                            $matchedCount++;
                        } else {
                            $mismatchedCount++;
                        }
                    }
                }
            }

            // ═══════════════════════════════════════════════════════════
            // BUILDING-LEVEL USAGE RULES
            // ═══════════════════════════════════════════════════════════
            $usageStatus      = 'NO_DATA';
            $usageStatusLabel = 'No Data';
            $usageBadgeClass  = 'badge-secondary';

            if ($buildingUsage && $assessmentUsage) {

                $buildingUsageUpper = strtoupper(trim($buildingUsage));
                $forceVariation = false;

                // MIXED rule
                if (str_contains($buildingUsageUpper, 'MIX')) {
                    if (!$hasResidential || !$hasCommercial) {
                        $forceVariation = true;
                    } elseif ($commercialIsNew) {
                        $forceVariation = true;
                    }
                }
                // COMMERCIAL family rule
                elseif (in_array($buildingUsageUpper, [
                    'COMMERCIAL', 'INDUSTRIAL', 'INSTITUTIONAL',
                    'GOVERNMENT', 'VACANT', 'OTHER',
                ])) {
                    if ($hasResidential) {
                        $forceVariation = true;
                    }
                }
                // RESIDENTIAL rule
                elseif ($buildingUsageUpper === 'RESIDENTIAL') {
                    if ($hasCommercial) {
                        $forceVariation = true;
                    }
                }

                if ($forceVariation) {
                    $usageStatus      = 'VARIATION';
                    $usageStatusLabel = 'Variation';
                    $usageBadgeClass  = 'badge-variation';
                } elseif ($mismatchedCount > 0) {
                    if ($matchedCount > 0 && $assessmentCount > 1) {
                        $usageStatus      = 'PARTIAL_MATCH';
                        $usageStatusLabel = 'Partial Match';
                        $usageBadgeClass  = 'badge-warning';
                    } else {
                        $usageStatus      = 'VARIATION';
                        $usageStatusLabel = 'Variation';
                        $usageBadgeClass  = 'badge-variation';
                    }
                } else {
                    $usageStatus      = 'MATCH';
                    $usageStatusLabel = 'Match';
                    $usageBadgeClass  = 'badge-match';
                }
            } elseif ($buildingUsage && !$assessmentUsage) {
                $usageStatus      = 'BUILDING_ONLY';
                $usageStatusLabel = 'Building Only';
                $usageBadgeClass  = 'badge-partial';
            } elseif (!$buildingUsage && $assessmentUsage) {
                $usageStatus      = 'ASSESSMENT_ONLY';
                $usageStatusLabel = 'Assessment Only';
                $usageBadgeClass  = 'badge-partial';
            }

            // ─── AREA VARIATION ───
            $areaVariation = $buildingArea - $assessmentArea;
            $variationPercentage = $buildingArea > 0
                ? round((abs($areaVariation) / $buildingArea) * 100, 1)
                : 0;

            // ─── RESULT ───
            $result[$gisid] = [
                'gisid'                => $gisid,
                'building_area'        => round($buildingArea, 2),
                'assessment_area'      => round($assessmentArea, 2),
                'area_variation'       => round($areaVariation, 2),
                'variation_percentage' => $variationPercentage,
                'area_status'          => abs($areaVariation) > 1 ? 'VARIATION' : 'MATCH',

                'building_usage'           => $buildingUsage,
                'assessment_usage'         => $assessmentUsage,
                'all_assessment_usages'    => $allAssessmentUsages,
                'all_assessment_types'     => $allAssessmentTypes,
                'has_multiple_assessments' => $assessmentCount > 1,

                'usage_matched_count'    => $matchedCount,
                'usage_mismatched_count' => $mismatchedCount,

                'usage_status'        => $usageStatus,
                'usage_status_label'  => $usageStatusLabel,
                'usage_badge_class'   => $usageBadgeClass,

                'assessment_count' => $assessmentCount,

                'has_residential'   => $hasResidential,
                'has_commercial'    => $hasCommercial,
                'commercial_is_new' => $commercialIsNew,
            ];
        }

        return $result;
    }

    /**
     * Filter variations via AJAX
     */
    public function filterVariations(Request $request)
    {
        $wardId = $request->ward_id;
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName    = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName   = "point_data_{$wardId}";
        $misTableName         = "mis_{$corp}";

        $polygons     = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas   = DB::table($pointDataTableName)->get();

        $misData    = $this->fetchMisData($misTableName, $wardNo);
        $allMisData = $this->fetchAllMisData($misTableName);

        $allVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);

        $filtered = array_filter($allVariations, function ($item) use ($request) {
            if ($request->usage_status != 'all' && $item['usage_status'] != $request->usage_status) {
                return false;
            }
            if ($request->area_status != 'all' && $item['area_status'] != strtoupper($request->area_status)) {
                return false;
            }
            if ($request->gisid && !str_contains($item['gisid'], $request->gisid)) {
                return false;
            }
            if ($request->assessment_count != 'all') {
                $count = (int)$request->assessment_count;
                if ($request->assessment_count == '3') {
                    if ($item['assessment_count'] < 3) return false;
                } else {
                    if ($item['assessment_count'] != $count) return false;
                }
            }
            if ($request->var_min && $item['variation_percentage'] < (float)$request->var_min) return false;
            if ($request->var_max && $item['variation_percentage'] > (float)$request->var_max) return false;
            return true;
        });

        $filtered = array_values($filtered);

        $stats = [
            'total' => count($allVariations),
            'filtered' => count($filtered),
            'usage_match' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'MATCH')),
            'usage_variation' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'VARIATION')),
            'usage_partial' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'PARTIAL_MATCH')),
            'usage_building_only' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'BUILDING_ONLY')),
            'usage_assessment_only' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'ASSESSMENT_ONLY')),
            'usage_no_data' => count(array_filter($filtered, fn($v) => $v['usage_status'] == 'NO_DATA')),
            'area_match' => count(array_filter($filtered, fn($v) => $v['area_status'] == 'MATCH')),
            'area_variation' => count(array_filter($filtered, fn($v) => $v['area_status'] == 'VARIATION')),
        ];

        return response()->json([
            'success' => true,
            'data' => $filtered,
            'stats' => $stats
        ]);
    }

    /**
     * Export variations
     */
    public function exportVariations(Request $request)
    {
        $wardId = $request->ward_id;
        $format = $request->format ?? 'xlsx';

        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName    = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName   = "point_data_{$wardId}";
        $misTableName         = "mis_{$corp}";

        $polygons     = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas   = DB::table($pointDataTableName)->get();

        $misData    = $this->fetchMisData($misTableName, $wardNo);
        $allMisData = $this->fetchAllMisData($misTableName);

        $allVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);

        $filtered = array_filter($allVariations, function ($item) use ($request) {
            if ($request->usage_status != 'all' && $item['usage_status'] != $request->usage_status) return false;
            if ($request->area_status != 'all' && $item['area_status'] != strtoupper($request->area_status)) return false;
            if ($request->gisid && !str_contains($item['gisid'], $request->gisid)) return false;
            if ($request->assessment_count != 'all') {
                $count = (int)$request->assessment_count;
                if ($request->assessment_count == '3') {
                    if ($item['assessment_count'] < 3) return false;
                } else {
                    if ($item['assessment_count'] != $count) return false;
                }
            }
            if ($request->var_min && $item['variation_percentage'] < (float)$request->var_min) return false;
            if ($request->var_max && $item['variation_percentage'] > (float)$request->var_max) return false;
            return true;
        });

        $filtered = array_values($filtered);

        $exportData = [];
        foreach ($filtered as $index => $item) {
            $exportData[] = [
                'S.No' => $index + 1,
                'GIS ID' => $item['gisid'],
                'Building Usage' => $item['building_usage'] ?? 'NULL',
                'Assessment Usage' => $item['assessment_usage'] ?? 'NULL',
                'Usage Status' => $item['usage_status_label'],
                'Building Area (sqft)' => number_format($item['building_area'], 2),
                'Assessment Area (sqft)' => number_format($item['assessment_area'], 2),
                'Area Variation' => number_format($item['area_variation'], 2),
                'Variation %' => number_format($item['variation_percentage'], 1),
                'Area Status' => $item['area_status'],
                'Assessment Count' => $item['assessment_count']
            ];
        }

        if ($format == 'pdf') {
            return $this->exportPdf($exportData, $ward);
        } elseif ($format == 'csv') {
            return $this->exportCsv($exportData, $ward);
        } else {
            return $this->exportExcel($exportData, $ward);
        }
    }

    private function exportExcel($data, $ward)
    {
        $filename = "ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys($data[0] ?? []);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        foreach ($data as $item) {
            $col = 'A';
            foreach ($item as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf($data, $ward)
    {
        $pdf = \PDF::loadView('exports.variation_pdf', [
            'data' => $data,
            'ward' => $ward,
            'date' => now()->format('d-m-Y H:i:s')
        ]);

        return $pdf->download("ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".pdf");
    }

    private function exportCsv($data, $ward)
    {
        $filename = "ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Central filter logic — reused across page load, AJAX pagination, and export
     */
    private function applyFilters($buildingVariations, Request $request)
    {
        if ($request->filled('usage_status') && $request->usage_status != 'all') {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                return ($item['usage_comparison']['usage_status'] ?? '') == $request->usage_status;
            });
        }

        if ($request->filled('area_status') && $request->area_status != 'all') {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                return strtoupper($item['area_comparison']['area_status'] ?? '') == strtoupper($request->area_status);
            });
        }

        if ($request->filled('assessment_type') && $request->assessment_type != 'all') {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                $assessmentType = $item['assessment']['details']['assessment_type_status'] ?? 'N/A';
                return strtoupper($assessmentType) == strtoupper($request->assessment_type);
            });
        }

        if ($request->filled('building_usage') && $request->building_usage != 'all') {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                $buildingUsage = $item['building']['usage'] ?? '';
                return strtoupper($buildingUsage) == strtoupper($request->building_usage);
            });
        }

        if ($request->filled('assessment_usage') && $request->assessment_usage != 'all') {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                $assessmentUsages = $item['assessment']['all_usages'] ?? [];
                return in_array($request->assessment_usage, $assessmentUsages);
            });
        }

        if ($request->filled('gisid')) {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                return stripos($item['gisid'], $request->gisid) !== false;
            });
        }

        if ($request->filled('var_min')) {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                return ($item['area_comparison']['variation_percentage'] ?? 0) >= floatval($request->var_min);
            });
        }

        if ($request->filled('var_max')) {
            $buildingVariations = array_filter($buildingVariations, function ($item) use ($request) {
                return ($item['area_comparison']['variation_percentage'] ?? 0) <= floatval($request->var_max);
            });
        }

        if ($request->filled('has_multiple') && $request->has_multiple == '1') {
            $buildingVariations = array_filter($buildingVariations, function ($item) {
                return ($item['assessment']['has_multiple'] ?? false) === true;
            });
        }

        return $buildingVariations;
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions($buildingVariations)
    {
        $options = [
            'usage_status' => [],
            'area_status' => ['MATCH', 'VARIATION'],
            'assessment_type' => [],
            'building_usage' => [],
            'assessment_usage' => [],
        ];

        foreach ($buildingVariations as $item) {
            $status = $item['usage_comparison']['usage_status'] ?? 'NO_DATA';
            if (!in_array($status, $options['usage_status'])) {
                $options['usage_status'][] = $status;
            }

            $assessmentType = $item['assessment']['details']['assessment_type_status'] ?? 'N/A';
            if ($assessmentType !== 'N/A' && !in_array($assessmentType, $options['assessment_type'])) {
                $options['assessment_type'][] = $assessmentType;
            }

            $buildingUsage = $item['building']['usage'] ?? null;
            if ($buildingUsage && !in_array($buildingUsage, $options['building_usage'])) {
                $options['building_usage'][] = $buildingUsage;
            }

            $assessmentUsages = $item['assessment']['all_usages'] ?? [];
            foreach ($assessmentUsages as $usage) {
                if ($usage && !in_array($usage, $options['assessment_usage'])) {
                    $options['assessment_usage'][] = $usage;
                }
            }
        }

        foreach ($options as $key => $value) {
            sort($options[$key]);
        }

        return $options;
    }

    public function dataControll($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);

        $corp   = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName    = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName   = "point_data_{$wardId}";
        $misTableName         = "mis_{$corp}";

        $polygons     = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas   = DB::table($pointDataTableName)->get();

        $misData    = $this->fetchMisData($misTableName, $wardNo);
        $allMisData = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData,
            $allMisData
        );

        $filterOptions = $this->getFilterOptions($buildingVariations);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        if ($perPage === 'all') {
            $perPage = max(count($buildingVariations), 1);
        }

        $total = count($buildingVariations);
        $paginatedData = array_slice($buildingVariations, ($page - 1) * $perPage, $perPage, true);

        $pagination = [
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $total,
            'last_page' => max((int) ceil($total / $perPage), 1),
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min(($page * $perPage), $total),
        ];

        return view('variation.data-details', [
            'buildingVariations' => $paginatedData,
            'allData' => $buildingVariations,
            'ward' => $ward,
            'zone' => $zone,
            'pagination' => $pagination,
            'filters' => $request->all(),
            'filterOptions' => $filterOptions,
        ]);
    }
/**
 * Build building data from polygons, polygon data, point data and MIS data
 */
private function buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData = null)
{
    $polygonDataByGisid = collect($polygonDatas)->keyBy('gisid');

    // ─── PRIMARY MIS LOOKUP (ward-filtered) ───
    $misByAssessment = collect($misData)->keyBy(function ($item) {
        return $this->normalizeAssessment($item->assessment ?? '');
    });

    // ─── FALLBACK MIS LOOKUP (all corp MIS) ───
    $allMisByAssessment = collect($allMisData ?? $misData)->keyBy(function ($item) {
        return $this->normalizeAssessment($item->assessment ?? '');
    });

    // Group point data by GIS ID
    $pointDataByGisid = [];
    foreach ($pointDatas as $pd) {
        $pointDataByGisid[$pd->point_gisid][] = $pd;
    }

    $result = [];

    foreach ($polygons as $polygon) {
        $gisid         = $polygon->gisid;
        $polygonSqfeet = floatval($polygon->sqfeet ?? 0);

        $polyData = $polygonDataByGisid->get($gisid);

        // ─────────────────────────────────────────────────────────
        // BUILDING USAGE & AREA
        // ─────────────────────────────────────────────────────────
        $buildingUsage   = null;
        $buildingArea    = $polygonSqfeet;
        $numberFloor     = 1;
        $basement        = 0;
        $percentage      = 0;
        $buildingDetails = [];

        if ($polyData) {
            $numberFloor = floatval($polyData->number_floor ?? 0);
            $basement    = floatval($polyData->basement ?? 0);
            $percentage  = floatval(($polyData->percentage / 100) ?? 0);

            $buildingArea = ($numberFloor > 0 ? $numberFloor + $percentage : 1) * $polygonSqfeet;

            if ($basement > 0) {
                $buildingArea += ($polygonSqfeet * $basement);
            }

            $buildingUsage = $polyData->building_usage ?? null;

            $buildingDetails = [
                'number_floor'             => $numberFloor,
                'basement'                 => $basement,
                'percentage'               => $percentage,
                'building_usage'           => $buildingUsage,
                'sqfeet'                   => $polygonSqfeet,
                'building_area_calculated' => round($buildingArea, 2),
            ];
        }

        // ─────────────────────────────────────────────────────────
        // ASSESSMENT DATA
        // ─────────────────────────────────────────────────────────
        $assessmentArea       = 0;
        $assessmentCount      = 0;
        $assessmentUsage      = null;
        $allAssessmentUsages  = [];
        $allAssessmentData    = null;
        $assessmentDetails    = [];
        $assessmentTypeStatus = 'N/A';

        $hasUsageMismatch = false;
        $hasPartialMatch  = false;
        $hasResidential   = false;
        $hasCommercial    = false;
        $commercialIsNew  = false;

        if (isset($pointDataByGisid[$gisid])) {
            $assessmentDetails['points'] = [];

            foreach ($pointDataByGisid[$gisid] as $pd) {
                $assessmentCount++;

                // ─── NORMALIZED MIS LOOKUP (ward first, then any ward) ───
                $assessmentKey = $this->normalizeAssessment($pd->assessment ?? '');
                $mis = $misByAssessment->get($assessmentKey);

                if (!$mis) {
                    $mis = $allMisByAssessment->get($assessmentKey);
                }

                // ─── POINT AREA (qcsqfeet first, then MIS plot_area) ───
                $pointArea = 0;
                $qcArea    = floatval($pd->qcsqfeet ?? 0);

                if ($qcArea > 0) {
                    $pointArea = $qcArea;
                } elseif ($mis && floatval($mis->plot_area ?? 0) > 0) {
                    $pointArea = floatval($mis->plot_area);
                }

                $assessmentArea += $pointArea;

                // ─── POINT USAGE ───
                $pointUsage      = $pd->qcusage ?? $pd->bill_usage ?? null;
                $pointUsageUpper = $pointUsage ? strtoupper(trim($pointUsage)) : null;

                // ─── POINT ASSESSMENT TYPE ───
                $pointAssessmentType = strtoupper(trim($pd->assessment_type ?? ''));

                if ($pointUsage) {
                    $allAssessmentUsages[] = $pointUsage;

                    if (!$assessmentUsage) {
                        $assessmentUsage = $pointUsage;
                    }

                    if ($pointUsageUpper === 'RESIDENTIAL') {
                        $hasResidential = true;
                    }
                    if ($pointUsageUpper === 'COMMERCIAL') {
                        $hasCommercial = true;

                        if ($pointAssessmentType === 'NEW') {
                            $commercialIsNew = true;
                        }
                    }
                }

                // ─────────────────────────────────────────────────────
                // ✅ POINT DATA — full pass-through (this is the fix)
                // ─────────────────────────────────────────────────────
                $assessmentDetails['points'][] = [
                    // Identity
                    'assessment'         => $pd->assessment         ?? null,
                    'old_assessment'     => $pd->old_assessment     ?? null,
                    'point_gisid'        => $pd->point_gisid        ?? null,
                    'assessment_type'    => $pd->assessment_type    ?? null,

                    // Area / usage (from point_data)
                    'point_area'         => $pointArea,
                    'qcsqfeet'           => $pd->qcsqfeet           ?? null,
                    'plot_area'          => $pd->plot_area          ?? null,
                    'qcusage'            => $pd->qcusage            ?? null,
                    'bill_usage'         => $pd->bill_usage         ?? null,

                    // Owner (from point_data)
                    'owner_name'         => $pd->owner_name         ?? null,
                    'present_owner_name' => $pd->present_owner_name ?? null,
                    'phone_number'       => $pd->phone_number       ?? null,
                    'aadhar_no'          => $pd->aadhar_no          ?? null,
                    'ration_no'          => $pd->ration_no          ?? null,

                    // Address (from point_data)
                    'old_door_no'        => $pd->old_door_no        ?? null,
                    'new_door_no'        => $pd->new_door_no        ?? null,
                    'floor'              => $pd->floor              ?? null,

                    // Tax (from point_data — if available)
                    'eb'                 => $pd->eb                 ?? null,
                    'water_tax'          => $pd->water_tax          ?? null,
                    'halfyeartax'        => $pd->halfyeartax        ?? null,
                    'balance'            => $pd->balance            ?? null,
                    'no_of_persons'      => $pd->no_of_persons      ?? null,

                    // QC / worker
                    'qc_name'            => $pd->qc_name            ?? null,
                    'qc_remarks'         => $pd->qc_remarks         ?? null,
                    'worker_name'        => $pd->worker_name        ?? null,
                    'remarks'            => $pd->remarks            ?? null,

                    // ─────────────────────────────────────────────
                    // ✅ MIS DATA — using REAL column names
                    //    half_year_tax (not halfyeartax!)
                    // ─────────────────────────────────────────────
                    'mis_data' => $mis ? [
                        'assessment'    => $mis->assessment    ?? null,
                        'plot_area'     => $mis->plot_area     ?? null,
                        'usage'         => $mis->usage         ?? null,
                        'ward_no'       => $mis->ward_no       ?? null,
                        'half_year_tax' => $mis->half_year_tax ?? null,   // ✅ correct
                        'balance'       => $mis->balance       ?? null,   // ✅ correct
                        'owner_name'    => $mis->owner_name    ?? null,
                        'phone_number'  => $mis->phone_number  ?? null,
                        'old_door_no'   => $mis->old_door_no   ?? null,
                        'new_door_no'   => $mis->new_door_no   ?? null,
                        'road_name'     => $mis->road_name     ?? null,
                        'type'          => $mis->type          ?? null,
                        'zone'          => $mis->zone          ?? null,
                    ] : null,
                ];

                $allAssessmentData = $pd;

                // ─── ASSESSMENT TYPE STATUS ───
                if ($pointAssessmentType === 'OLD') {
                    $assessmentTypeStatus = 'OLD ASSESSMENT';
                } elseif ($pointAssessmentType === 'NEW') {
                    $assessmentTypeStatus = 'NEW ASSESSMENT';
                } else {
                    $assessmentTypeStatus = 'OTHER';
                }
            }

            // ═══════════════════════════════════════════════════════════
            // USAGE COMPARISON
            // ═══════════════════════════════════════════════════════════
            if ($buildingUsage && $assessmentUsage) {
                $buildingUsageUpper = strtoupper(trim($buildingUsage));

                // 1) MIXED
                if (str_contains($buildingUsageUpper, 'MIX')) {
                    if (!$hasResidential || !$hasCommercial) {
                        $hasUsageMismatch = true;
                    } elseif ($commercialIsNew) {
                        $hasUsageMismatch = true;
                    } else {
                        $hasPartialMatch = true;
                    }
                }
                // 2) RESIDENTIAL
                elseif ($buildingUsageUpper === 'RESIDENTIAL') {
                    $allResidential = true;
                    foreach ($allAssessmentUsages as $u) {
                        if (strtoupper(trim($u)) !== 'RESIDENTIAL') {
                            $allResidential = false;
                            break;
                        }
                    }
                    if ($allResidential) {
                        $hasPartialMatch = true;
                    } else {
                        $hasUsageMismatch = true;
                    }
                }
                // 3) COMMERCIAL family
                elseif (in_array($buildingUsageUpper, [
                    'COMMERCIAL', 'INDUSTRIAL', 'INSTITUTIONAL',
                    'GOVERNMENT', 'VACANT', 'OTHER',
                ])) {
                    $hasResidentialBill = false;
                    foreach ($allAssessmentUsages as $u) {
                        if (strtoupper(trim($u)) === 'RESIDENTIAL') {
                            $hasResidentialBill = true;
                            break;
                        }
                    }
                    if ($hasResidentialBill) {
                        $hasUsageMismatch = true;
                    } else {
                        $hasPartialMatch = true;
                    }
                }
                // 4) Unknown
                else {
                    \Log::warning('Unknown building usage type encountered', [
                        'gisid'          => $gisid,
                        'building_usage' => $buildingUsage,
                        'normalized'     => $buildingUsageUpper,
                    ]);
                    $hasUsageMismatch = true;
                }
            }

            $assessmentDetails['assessment_type_status'] = $assessmentTypeStatus;
            $assessmentDetails['total_assessment_area']   = round($assessmentArea, 2);
            $assessmentDetails['assessment_count']        = $assessmentCount;
        }

        // ─────────────────────────────────────────────────────────
        // USAGE STATUS FINAL
        // ─────────────────────────────────────────────────────────
        $usageStatus      = 'NO_DATA';
        $usageStatusLabel = 'No Data';
        $usageBadgeClass  = 'badge-secondary';

        if ($buildingUsage && $assessmentUsage) {
            $buildingUsageUpper = strtoupper(trim($buildingUsage));

            if ($hasUsageMismatch) {
                if (str_contains($buildingUsageUpper, 'MIX')) {
                    $usageStatus      = 'VARIATION';
                    $usageStatusLabel = 'Variation';
                    $usageBadgeClass  = 'badge-variation';
                } elseif (in_array($buildingUsageUpper, [
                    'COMMERCIAL', 'INDUSTRIAL', 'INSTITUTIONAL',
                    'GOVERNMENT', 'VACANT', 'OTHER',
                ])) {
                    $usageStatus      = 'VARIATION';
                    $usageStatusLabel = 'Variation';
                    $usageBadgeClass  = 'badge-variation';
                } elseif ($hasPartialMatch && count($allAssessmentUsages) > 1) {
                    $usageStatus      = 'PARTIAL_MATCH';
                    $usageStatusLabel = 'Partial Match';
                    $usageBadgeClass  = 'badge-warning';
                } else {
                    $usageStatus      = 'VARIATION';
                    $usageStatusLabel = 'Variation';
                    $usageBadgeClass  = 'badge-variation';
                }
            } else {
                $usageStatus      = 'MATCH';
                $usageStatusLabel = 'Match';
                $usageBadgeClass  = 'badge-match';
            }
        } elseif ($buildingUsage && !$assessmentUsage) {
            $usageStatus      = 'BUILDING_ONLY';
            $usageStatusLabel = 'Building Only';
            $usageBadgeClass  = 'badge-partial';
        } elseif (!$buildingUsage && $assessmentUsage) {
            $usageStatus      = 'ASSESSMENT_ONLY';
            $usageStatusLabel = 'Assessment Only';
            $usageBadgeClass  = 'badge-partial';
        }

        // ─────────────────────────────────────────────────────────
        // AREA VARIATION
        // ─────────────────────────────────────────────────────────
        $rawDifference        = $buildingArea - $assessmentArea;
        $areaVariation        = max(0, round($rawDifference, 2));
        $hasExcessDeclaration = $rawDifference < 0;

        $variationPercentage = $buildingArea > 0
            ? round(($areaVariation / $buildingArea) * 100, 1)
            : 0;

        // ─────────────────────────────────────────────────────────
        // RESULT
        // ─────────────────────────────────────────────────────────
        $result[$gisid] = [
            'gisid'   => $gisid,
            'polygon' => [
                'sqfeet'      => $polygonSqfeet,
                'coordinates' => $polygon->coordinates ?? null,
                'geometry'    => $polygon->geometry    ?? null,
            ],
            'building' => [
                'area'     => round($buildingArea, 2),
                'usage'    => $buildingUsage,
                'details'  => $buildingDetails,
                'raw_data' => $polyData ? (array) $polyData : null,
            ],
            'assessment' => [
                'area'         => round($assessmentArea, 2),
                'usage'        => $assessmentUsage,
                'count'        => $assessmentCount,
                'all_usages'   => $allAssessmentUsages,
                'has_multiple' => count($allAssessmentUsages) > 1,
                'details'      => $assessmentDetails,
                'raw_data'     => $allAssessmentData ? (array) $allAssessmentData : null,
            ],
            'area_comparison' => [
                'building_area'          => round($buildingArea, 2),
                'assessment_area'        => round($assessmentArea, 2),
                'area_variation'         => $areaVariation,
                'variation_percentage'   => $variationPercentage,
                'has_excess_declaration' => $hasExcessDeclaration,
                'area_status'            => $areaVariation > 1 ? 'VARIATION' : 'MATCH',
                'status_label'           => $areaVariation > 1 ? 'Area Variation' : 'Area Match',
                'status_badge'           => $areaVariation > 1 ? 'badge-warning' : 'badge-success',
            ],
            'usage_comparison' => [
                'building_usage'           => $buildingUsage,
                'assessment_usage'         => $assessmentUsage,
                'all_assessment_usages'    => $allAssessmentUsages,
                'has_multiple_assessments' => count($allAssessmentUsages) > 1,
                'usage_status'             => $usageStatus,
                'usage_status_label'       => $usageStatusLabel,
                'usage_badge_class'        => $usageBadgeClass,
                'has_mismatch'             => $hasUsageMismatch,
                'has_partial_match'        => $hasPartialMatch,
                'has_residential'          => $hasResidential,
                'has_commercial'           => $hasCommercial,
                'commercial_is_new'        => $commercialIsNew,
            ],
            'raw_data' => [
                'polygon_data' => $polyData ? (array) $polyData : null,
                'point_data'   => isset($pointDataByGisid[$gisid])
                    ? array_map(fn($item) => (array) $item, $pointDataByGisid[$gisid])
                    : null,
                'mis_data'     => isset($pointDataByGisid[$gisid])
                    ? array_map(function ($pd) use ($misByAssessment, $allMisByAssessment) {
                        $key = $this->normalizeAssessment($pd->assessment ?? '');
                        $mis = $misByAssessment->get($key) ?? $allMisByAssessment->get($key);
                        return $mis ? (array) $mis : null;
                    }, $pointDataByGisid[$gisid] ?? [])
                    : null,
            ],
        ];
    }

    return $result;
}

    /**
     * AJAX — building details modal
     */
    public function getBuildingDetails($wardId, $gisid)
    {
        try {
            $ward = Ward::findOrFail($wardId);
            $zone = Zone::findOrFail($ward->zone_id);

            $corp   = $zone->corp_id;
            $wardNo = $ward->ward_no;

            $polygons     = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
            $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
            $pointDatas   = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();

            $misTableName = "mis_{$corp}";
            $misData      = $this->fetchMisData($misTableName, $wardNo);
            $allMisData   = $this->fetchAllMisData($misTableName);

            $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
            $data = $buildingVariations[$gisid] ?? null;

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found for GIS ID: ' . $gisid,
                ], 404);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX — paginated data
     */
    public function getPaginatedData($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons     = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas   = DB::table("point_data_{$wardId}")->get();

        $misTableName = "mis_{$corp}";
        $misData      = $this->fetchMisData($misTableName, $wardNo);
        $allMisData   = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $total = count($buildingVariations);
        $paginatedData = array_slice($buildingVariations, ($page - 1) * $perPage, $perPage, true);

        return response()->json([
            'success' => true,
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min(($page * $perPage), $total),
            ],
        ]);
    }

    /**
     * Export ALL (or filtered) data to Excel
     */
    public function exportVariation($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons     = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas   = DB::table("point_data_{$wardId}")->get();

        $misTableName = "mis_{$corp}";
        $misData      = $this->fetchMisData($misTableName, $wardNo);
        $allMisData   = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Variation');

        $headers = [
            'S.No', 'GIS ID', 'Building Usage', 'Building Area (sqft)',
            'Assessment Usage', 'Assessment Area (sqft)', 'Area Variation (sqft)',
            'Variation %', 'Area Status', 'Usage Status', 'Floor Count',
            'Basement', 'Percentage', 'Assessment Count', 'Assessment Type',
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A3C6E']],
            'alignment' => ['horizontal' => 'center'],
        ];

        foreach ($headers as $index => $header) {
            $col = $index + 1;
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $sheet->getStyleByColumnAndRow($col, 1)->applyFromArray($headerStyle);
        }

        $row = 2;
        $sno = 1;
        foreach ($buildingVariations as $gisid => $item) {
            $sheet->setCellValueByColumnAndRow(1, $row, $sno++);
            $sheet->setCellValueByColumnAndRow(2, $row, $gisid);
            $sheet->setCellValueByColumnAndRow(3, $row, $item['building']['usage'] ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(4, $row, $item['building']['area'] ?? 0);
            $sheet->setCellValueByColumnAndRow(5, $row, $item['assessment']['usage'] ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(6, $row, $item['assessment']['area'] ?? 0);
            $sheet->setCellValueByColumnAndRow(7, $row, $item['area_comparison']['area_variation'] ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row, $item['area_comparison']['variation_percentage'] ?? 0);
            $sheet->setCellValueByColumnAndRow(9, $row, $item['area_comparison']['area_status'] ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(10, $row, $item['usage_comparison']['usage_status_label'] ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(11, $row, $item['building']['details']['number_floor'] ?? 0);
            $sheet->setCellValueByColumnAndRow(12, $row, $item['building']['details']['basement'] ?? 0);
            $sheet->setCellValueByColumnAndRow(13, $row, $item['building']['details']['percentage'] ?? 0);
            $sheet->setCellValueByColumnAndRow(14, $row, $item['assessment']['count'] ?? 0);
            $sheet->setCellValueByColumnAndRow(15, $row, $item['assessment']['details']['assessment_type_status'] ?? 'N/A');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "data_variation_ward_{$wardId}_" . date('Y-m-d_H-i-s') . ".xlsx";

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function exportPdffile($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons     = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas   = DB::table("point_data_{$wardId}")->get();

        $misTableName = "mis_{$corp}";
        $misData      = $this->fetchMisData($misTableName, $wardNo);
        $allMisData   = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $pdf = PDF::loadView('variation.pdf-export', [
            'buildingVariations' => $buildingVariations,
            'ward' => $ward,
            'zone' => $zone,
            'date' => now()->format('d-m-Y H:i:s'),
        ]);

        return $pdf->download("data_variation_ward_{$wardId}_" . date('Y-m-d_H-i-s') . ".pdf");
    }

    public function exportSinglePdf($wardId, $gisid)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons     = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
        $pointDatas   = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();

        $misTableName = "mis_{$corp}";
        $misData      = $this->fetchMisData($misTableName, $wardNo);
        $allMisData   = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
        $data = $buildingVariations[$gisid] ?? null;

        if (!$data) {
            abort(404, 'Data not found for GIS ID: ' . $gisid);
        }

        $pdf = Pdf::loadView('variation.single-pdf-export', [
            'data' => $data,
            'ward' => $ward,
            'zone' => $zone,
            'gisid' => $gisid,
            'date' => now()->format('d-m-Y H:i:s'),
        ]);

        return $pdf->download("FORM2_{$gisid}_" . date('Y-m-d_H-i-s') . ".pdf");
    }

    public function exportSingleAssessmentPdf(Request $request, $wardId)
    {
        $gisid = $request->query('gisid');
        $assessmentNo = $request->query('assessment');
        $assessmentType = $request->query('assessment_type', 'N/A');

        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons     = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
        $pointDatas   = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();

        $misTableName = "mis_{$corp}";
        $misData      = $this->fetchMisData($misTableName, $wardNo);
        $allMisData   = $this->fetchAllMisData($misTableName);

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData, $allMisData);
        $buildingData = $buildingVariations[$gisid] ?? null;

        if (!$buildingData) {
            return redirect()->back()->with('error', 'Building not found');
        }

        $assessmentData = null;
        $allPoints = $buildingData['assessment']['details']['points'] ?? [];

        foreach ($allPoints as $point) {
            if ($point['assessment'] == $assessmentNo) {
                $assessmentData = $point;
                break;
            }
        }

        if (!$assessmentData) {
            return redirect()->back()->with('error', 'Assessment not found');
        }

        $pdf = Pdf::loadView('variation.single-assessment-pdf', [
            'ward' => $ward,
            'zone' => $zone,
            'gisid' => $gisid,
            'buildingData' => $buildingData,
            'assessmentData' => $assessmentData,
            'assessmentNo' => $assessmentNo,
            'assessmentType' => $assessmentType,
            'date' => now()->format('d-m-Y'),
            'time' => now()->format('h-i-A'),
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $safeGisid = preg_replace('/[\/\\\:]/', '-', $gisid);
        $safeAssessmentNo = preg_replace('/[\/\\\:]/', '-', $assessmentNo);
        $filename = "Assessment_{$safeAssessmentNo}_GIS_{$safeGisid}_" . date('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }
public function exportAllAssessmentsPdf($wardId, $gisid)
{
    $ward   = Ward::findOrFail($wardId);
    $zone   = Zone::findOrFail($ward->zone_id);
    $corp   = $zone->corp_id;
    $wardNo = $ward->ward_no;

    $polygons     = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
    $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
    $pointDatas   = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();

    $misTableName = "mis_{$corp}";
    $misData      = $this->fetchMisData($misTableName, $wardNo);
    $allMisData   = $this->fetchAllMisData($misTableName);

    $buildingVariations = $this->buildBuildingData(
        $polygons,
        $polygonDatas,
        $pointDatas,
        $misData,
        $allMisData
    );

    $buildingData = $buildingVariations[$gisid] ?? null;

    if (!$buildingData) {
        return redirect()->back()->with('error', 'Building not found');
    }

    // ─────────────────────────────────────────────────────────────
    // BUILDING IMAGE from polygon_data
    // ─────────────────────────────────────────────────────────────
    $buildingImage = null;

    if ($polygonDatas->isNotEmpty()) {
        $pd = $polygonDatas->first();

        // ⚠️ Change 'image' to your real column name if different
        $imageValue = $pd->image ?? null;

        if ($imageValue) {
            if (str_starts_with($imageValue, 'data:image')) {
                // Already base64 data URI
                $buildingImage = $imageValue;
            } elseif (filter_var($imageValue, FILTER_VALIDATE_URL)) {
                // Full URL
                $buildingImage = $imageValue;
            } else {
                // File path — try common locations
                $paths = [
                    storage_path("app/public/{$imageValue}"),
                    public_path($imageValue),
                    public_path("storage/{$imageValue}"),
                    storage_path("app/{$imageValue}"),
                    base_path($imageValue),
                ];

                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $mime = mime_content_type($path) ?: 'image/jpeg';
                        $buildingImage = 'data:' . $mime . ';base64,'
                                       . base64_encode(file_get_contents($path));
                        break;
                    }
                }
            }
        }
    }

    $pdf = Pdf::loadView('variation.all-assessments-pdf', [
        'ward'          => $ward,
        'zone'          => $zone,
        'gisid'         => $gisid,
        'buildingData'  => $buildingData,
        'buildingImage' => $buildingImage,
        'date'          => now()->format('d-m-Y'),
        'time'          => now()->format('h-i-A'),
    ]);

    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions([
        'defaultFont'          => 'DejaVu Sans',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => true,
    ]);

    $safeGisid = preg_replace('/[\/\\\:]/', '-', $gisid);
    $filename  = "All_Assessments_GIS_{$safeGisid}_" . date('Y-m-d') . ".pdf";

    return $pdf->download($filename);
}
}
