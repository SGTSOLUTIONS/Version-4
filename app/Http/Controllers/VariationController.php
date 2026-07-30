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
    /**
     * Area Variation
     */
    public function areaVariation($wardId)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);

        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        // Dynamic table names
        $polygonsTableName = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";

        // Fetch GIS Data
        $polygons = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        // MIS Data
        $misTableName = "mis_{$corp}";
        $misData = DB::table($misTableName)
            ->where('ward_no', $wardNo)
            ->get();

        // Build variations
        $buildingVariations = $this->buildBuildingVariations(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData
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

        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";
        $misTableName = "mis_{$corp}";

        $polygons = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        $misData = DB::table($misTableName)
            ->where('ward_no', $wardNo)
            ->get();

        $buildingVariations = $this->buildBuildingVariations(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData
        );

        return view('variation.usage_variation', compact(
            'ward',
            'zone',
            'buildingVariations'
        ));
    }

    /**
     * Build Area & Usage Variation with All Use Cases
     */
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

            // ─── BUILDING USAGE ───
            $buildingUsage = null;
            $buildingArea = $polygonSqfeet;
            $numberFloor = 1;
            $basement = 0;
            $percentage = 0;

            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);
                $percentage = floatval(($polyData->percentage / 100) ?? 0);

                $buildingArea = ($numberFloor > 0 ? $numberFloor + $percentage : 1) * $polygonSqfeet;

                if ($basement > 0) {
                    $buildingArea += ($polygonSqfeet * $basement);
                }

                $buildingUsage = $polyData->building_usage ?? null;
            }

            // ─── ASSESSMENT DATA ───
            $assessmentArea = 0;
            $assessmentCount = 0;
            $assessmentUsage = null;
            $allAssessmentUsages = [];
            $hasUsageMismatch = false;
            $hasPartialMatch = false;

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

                    // ─── GET ASSESSMENT USAGE ───
                    $pointUsage = $pd->qcusage ?? $pd->bill_usage ?? null;

                    if ($pointUsage) {
                        $allAssessmentUsages[] = $pointUsage;
                    }

                    // Store the first assessment usage for display
                    if (!$assessmentUsage && $pointUsage) {
                        $assessmentUsage = $pointUsage;
                    }

                    // ─── USAGE MISMATCH CHECK ───
                    if ($buildingUsage && $pointUsage) {
                        if (strtoupper(trim($buildingUsage)) !== strtoupper(trim($pointUsage))) {
                            $hasUsageMismatch = true;
                        } else {
                            $hasPartialMatch = true;
                        }
                    }
                }
            }

            // ─── DETERMINE USAGE STATUS ───
            $usageStatus = 'NO_DATA';
            $usageStatusLabel = 'No Data';
            $usageBadgeClass = 'badge-secondary';

            // Case 1: Building Usage exists, Assessment Usage exists
            if ($buildingUsage && $assessmentUsage) {
                if ($hasUsageMismatch) {
                    // Check if there are multiple assessments and some match
                    if ($hasPartialMatch && count($allAssessmentUsages) > 1) {
                        $usageStatus = 'PARTIAL_MATCH';
                        $usageStatusLabel = 'Partial Match';
                        $usageBadgeClass = 'badge-warning';
                    } else {
                        $usageStatus = 'VARIATION';
                        $usageStatusLabel = 'Variation';
                        $usageBadgeClass = 'badge-variation';
                    }
                } else {
                    $usageStatus = 'MATCH';
                    $usageStatusLabel = 'Match';
                    $usageBadgeClass = 'badge-match';
                }
            }
            // Case 2: Building Usage exists, Assessment Usage is NULL
            elseif ($buildingUsage && !$assessmentUsage) {
                $usageStatus = 'BUILDING_ONLY';
                $usageStatusLabel = 'Building Only';
                $usageBadgeClass = 'badge-partial';
            }
            // Case 3: Building Usage is NULL, Assessment Usage exists
            elseif (!$buildingUsage && $assessmentUsage) {
                $usageStatus = 'ASSESSMENT_ONLY';
                $usageStatusLabel = 'Assessment Only';
                $usageBadgeClass = 'badge-partial';
            }
            // Case 4: Both are NULL
            else {
                $usageStatus = 'NO_DATA';
                $usageStatusLabel = 'No Data';
                $usageBadgeClass = 'badge-secondary';
            }

            $areaVariation = $buildingArea - $assessmentArea;
            $variationPercentage = $buildingArea > 0
                ? round((abs($areaVariation) / $buildingArea) * 100, 1)
                : 0;

            $result[$gisid] = [
                'gisid' => $gisid,
                'building_area' => round($buildingArea, 2),
                'assessment_area' => round($assessmentArea, 2),
                'area_variation' => round($areaVariation, 2),
                'variation_percentage' => $variationPercentage,
                'area_status' => abs($areaVariation) > 1 ? 'VARIATION' : 'MATCH',

                // ─── USAGE DETAILS ───
                'building_usage' => $buildingUsage,
                'assessment_usage' => $assessmentUsage,
                'all_assessment_usages' => $allAssessmentUsages,
                'has_multiple_assessments' => count($allAssessmentUsages) > 1,

                // ─── USAGE STATUS WITH LABELS ───
                'usage_status' => $usageStatus,
                'usage_status_label' => $usageStatusLabel,
                'usage_badge_class' => $usageBadgeClass,

                'assessment_count' => $assessmentCount,
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

        // Fetch data
        $polygonsTableName = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";
        $misTableName = "mis_{$corp}";

        $polygons = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();
        $misData = DB::table($misTableName)->where('ward_no', $wardNo)->get();

        // Build variations
        $allVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData);

        // Apply filters
        $filtered = array_filter($allVariations, function ($item) use ($request) {
            // Usage status filter
            if ($request->usage_status != 'all' && $item['usage_status'] != $request->usage_status) {
                return false;
            }
            // Area status filter
            if ($request->area_status != 'all' && $item['area_status'] != strtoupper($request->area_status)) {
                return false;
            }
            // GIS ID filter
            if ($request->gisid && !str_contains($item['gisid'], $request->gisid)) {
                return false;
            }
            // Assessment count filter
            if ($request->assessment_count != 'all') {
                $count = (int)$request->assessment_count;
                if ($request->assessment_count == '3') {
                    if ($item['assessment_count'] < 3) return false;
                } else {
                    if ($item['assessment_count'] != $count) return false;
                }
            }
            // Variation percentage range
            if ($request->var_min && $item['variation_percentage'] < (float)$request->var_min) return false;
            if ($request->var_max && $item['variation_percentage'] > (float)$request->var_max) return false;
            return true;
        });

        // Re-index array
        $filtered = array_values($filtered);

        // Calculate stats
        $stats = [
            'total' => count($allVariations),
            'filtered' => count($filtered),
            'usage_match' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'MATCH';
            })),
            'usage_variation' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'VARIATION';
            })),
            'usage_partial' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'PARTIAL_MATCH';
            })),
            'usage_building_only' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'BUILDING_ONLY';
            })),
            'usage_assessment_only' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'ASSESSMENT_ONLY';
            })),
            'usage_no_data' => count(array_filter($filtered, function ($v) {
                return $v['usage_status'] == 'NO_DATA';
            })),
            'area_match' => count(array_filter($filtered, function ($v) {
                return $v['area_status'] == 'MATCH';
            })),
            'area_variation' => count(array_filter($filtered, function ($v) {
                return $v['area_status'] == 'VARIATION';
            })),
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

        // Fetch data
        $polygonsTableName = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";
        $misTableName = "mis_{$corp}";

        $polygons = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();
        $misData = DB::table($misTableName)->where('ward_no', $wardNo)->get();

        // Build variations
        $allVariations = $this->buildBuildingVariations($polygons, $polygonDatas, $pointDatas, $misData);

        // Apply filters
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

        // Prepare export data
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

    /**
     * Export to Excel
     */
    private function exportExcel($data, $ward)
    {
        $filename = "ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = array_keys($data[0] ?? []);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($data as $item) {
            $col = 'A';
            foreach ($item as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Set response headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export to PDF
     */
    private function exportPdf($data, $ward)
    {
        $pdf = \PDF::loadView('exports.variation_pdf', [
            'data' => $data,
            'ward' => $ward,
            'date' => now()->format('d-m-Y H:i:s')
        ]);

        return $pdf->download("ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".pdf");
    }

    /**
     * Export to CSV
     */
    private function exportCsv($data, $ward)
    {
        $filename = "ward_{$ward->ward_no}_variations_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }



   public function dataControll($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);

        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygonsTableName = "polygons_{$wardId}";
        $polygonDataTableName = "polygon_data_{$wardId}";
        $pointDataTableName = "point_data_{$wardId}";
        $misTableName = "mis_{$corp}";

        $polygons = DB::table($polygonsTableName)->get();
        $polygonDatas = DB::table($polygonDataTableName)->get();
        $pointDatas = DB::table($pointDataTableName)->get();

        $misData = DB::table($misTableName)
            ->where('ward_no', $wardNo)
            ->get();

        $buildingVariations = $this->buildBuildingData(
            $polygons,
            $polygonDatas,
            $pointDatas,
            $misData
        );

        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        // Pagination
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
        ]);
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
                return strtoupper($item['assessment']['details']['assessment_type_status'] ?? '') == strtoupper($request->assessment_type);
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
     * Build building data from polygons, polygon data, point data and MIS data
     */
    private function buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData)
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

            // ─── BUILDING USAGE ───
            $buildingUsage = null;
            $buildingArea = $polygonSqfeet;
            $numberFloor = 1;
            $basement = 0;
            $percentage = 0;
            $buildingDetails = [];

            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);
                $percentage = floatval(($polyData->percentage / 100) ?? 0);

                $buildingArea = ($numberFloor > 0 ? $numberFloor + $percentage : 1) * $polygonSqfeet;

                if ($basement > 0) {
                    $buildingArea += ($polygonSqfeet * $basement);
                }

                $buildingUsage = $polyData->building_usage ?? null;

                $buildingDetails = [
                    'number_floor' => $numberFloor,
                    'basement' => $basement,
                    'percentage' => $percentage,
                    'building_usage' => $buildingUsage,
                    'sqfeet' => $polygonSqfeet,
                    'building_area_calculated' => round($buildingArea, 2),
                ];
            }

            // ─── ASSESSMENT DATA ───
            $assessmentArea = 0;
            $assessmentCount = 0;
            $assessmentUsage = null;
            $allAssessmentUsages = [];
            $allAssessmentData = [];
            $assessmentDetails = [];
            $hasUsageMismatch = false;
            $hasPartialMatch = false;
            $assessmentTypeStatus = 'N/A';

            if (isset($pointDataByGisid[$gisid])) {
                $assessmentDetails['points'] = [];

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
                    if ($pointUsage) {
                        $allAssessmentUsages[] = $pointUsage;
                    }

                    if (!$assessmentUsage && $pointUsage) {
                        $assessmentUsage = $pointUsage;
                    }

                    $assessmentDetails['points'][] = [
                        'assessment' => $pd->assessment,
                        'point_gisid' => $pd->point_gisid,
                        'point_area' => $pointArea,
                        'qcusage' => $pd->qcusage ?? null,
                        'bill_usage' => $pd->bill_usage ?? null,
                        'qcsqfeet' => $pd->qcsqfeet ?? null,
                        'assessment_type' => $pd->assessment_type ?? null,
                        'mis_data' => $mis ? [
                            'plot_area' => $mis->plot_area ?? null,
                            'assessment' => $mis->assessment ?? null,
                            'usage' => $mis->usage ?? null,
                        ] : null,
                    ];

                    $allAssessmentData = $pd;

                    if ($buildingUsage && $pointUsage) {
                        $buildingUsageUpper = strtoupper(trim($buildingUsage));
                        $pointUsageUpper = strtoupper(trim($pointUsage));

                        $allowedAssessmentUsage = match ($buildingUsageUpper) {
                            'RESIDENTIAL' => ['RESIDENTIAL'],
                            'COMMERCIAL', 'INDUSTRIAL', 'INSTITUTIONAL' => ['COMMERCIAL'],
                            'MIXED' => ['RESIDENTIAL', 'COMMERCIAL'],
                            'GOVERNMENT' => ['COMMERCIAL'],
                            'VACANT' => ['COMMERCIAL'],
                            'OTHER' => ['COMMERCIAL'],
                            default => [],
                        };

                        if (in_array($pointUsageUpper, $allowedAssessmentUsage)) {
                            $hasPartialMatch = true;
                        } else {
                            $hasUsageMismatch = true;
                        }
                    }

                    $assessmentType = strtoupper(trim($pd->assessment_type ?? ''));
                    if ($assessmentType === 'OLD') {
                        $assessmentTypeStatus = 'OLD ASSESSMENT';
                    } elseif ($assessmentType === 'NEW') {
                        $assessmentTypeStatus = 'NEW ASSESSMENT';
                    } else {
                        $assessmentTypeStatus = 'OTHER';
                    }
                }

                $assessmentDetails['assessment_type_status'] = $assessmentTypeStatus;
                $assessmentDetails['total_assessment_area'] = round($assessmentArea, 2);
                $assessmentDetails['assessment_count'] = $assessmentCount;
            }

            // ─── USAGE STATUS ───
            $usageStatus = 'NO_DATA';
            $usageStatusLabel = 'No Data';
            $usageBadgeClass = 'badge-secondary';

            if ($buildingUsage && $assessmentUsage) {
                if ($hasUsageMismatch) {
                    if ($hasPartialMatch && count($allAssessmentUsages) > 1) {
                        $usageStatus = 'PARTIAL_MATCH';
                        $usageStatusLabel = 'Partial Match';
                        $usageBadgeClass = 'badge-warning';
                    } else {
                        $usageStatus = 'VARIATION';
                        $usageStatusLabel = 'Variation';
                        $usageBadgeClass = 'badge-variation';
                    }
                } else {
                    $usageStatus = 'MATCH';
                    $usageStatusLabel = 'Match';
                    $usageBadgeClass = 'badge-match';
                }
            } elseif ($buildingUsage && !$assessmentUsage) {
                $usageStatus = 'BUILDING_ONLY';
                $usageStatusLabel = 'Building Only';
                $usageBadgeClass = 'badge-partial';
            } elseif (!$buildingUsage && $assessmentUsage) {
                $usageStatus = 'ASSESSMENT_ONLY';
                $usageStatusLabel = 'Assessment Only';
                $usageBadgeClass = 'badge-partial';
            }

            // ─── AREA VARIATION — FIXED ───
            // Raw difference: positive = building bigger than assessment (under-declared, the case we care about).
            // Negative would mean assessment > building, which is NOT a "variation" we want to flag —
            // so we clamp it at 0 instead of showing a negative number.
            $rawDifference = $buildingArea - $assessmentArea;
            $areaVariation = max(0, round($rawDifference, 2));

            // Track the excess-declaration case separately (informational only, not a "variation")
            $hasExcessDeclaration = $rawDifference < 0;

            $variationPercentage = $buildingArea > 0
                ? round(($areaVariation / $buildingArea) * 100, 1)
                : 0;

            $result[$gisid] = [
                'gisid' => $gisid,
                'polygon' => [
                    'sqfeet' => $polygonSqfeet,
                    'coordinates' => $polygon->coordinates ?? null,
                    'geometry' => $polygon->geometry ?? null,
                ],
                'building' => [
                    'area' => round($buildingArea, 2),
                    'usage' => $buildingUsage,
                    'details' => $buildingDetails,
                    'raw_data' => $polyData ? (array) $polyData : null,
                ],
                'assessment' => [
                    'area' => round($assessmentArea, 2),
                    'usage' => $assessmentUsage,
                    'count' => $assessmentCount,
                    'all_usages' => $allAssessmentUsages,
                    'has_multiple' => count($allAssessmentUsages) > 1,
                    'details' => $assessmentDetails,
                    'raw_data' => $allAssessmentData ? (array) $allAssessmentData : null,
                ],
                'area_comparison' => [
                    'building_area' => round($buildingArea, 2),
                    'assessment_area' => round($assessmentArea, 2),
                    'area_variation' => $areaVariation,               // always >= 0 now
                    'variation_percentage' => $variationPercentage,
                    'has_excess_declaration' => $hasExcessDeclaration, // true if assessment > building
                    'area_status' => $areaVariation > 1 ? 'VARIATION' : 'MATCH',
                    'status_label' => $areaVariation > 1 ? 'Area Variation' : 'Area Match',
                    'status_badge' => $areaVariation > 1 ? 'badge-warning' : 'badge-success',
                ],
                'usage_comparison' => [
                    'building_usage' => $buildingUsage,
                    'assessment_usage' => $assessmentUsage,
                    'all_assessment_usages' => $allAssessmentUsages,
                    'has_multiple_assessments' => count($allAssessmentUsages) > 1,
                    'usage_status' => $usageStatus,
                    'usage_status_label' => $usageStatusLabel,
                    'usage_badge_class' => $usageBadgeClass,
                    'has_mismatch' => $hasUsageMismatch,
                    'has_partial_match' => $hasPartialMatch,
                ],
                'raw_data' => [
                    'polygon_data' => $polyData ? (array) $polyData : null,
                    'point_data' => isset($pointDataByGisid[$gisid])
                        ? array_map(fn ($item) => (array) $item, $pointDataByGisid[$gisid])
                        : null,
                    'mis_data' => isset($pointDataByGisid[$gisid])
                        ? array_map(function ($pd) use ($misByAssessment) {
                            $mis = $misByAssessment->get($pd->assessment);
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

            $corp = $zone->corp_id;
            $wardNo = $ward->ward_no;

            $polygons = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
            $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
            $pointDatas = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();
            $misData = DB::table("mis_{$corp}")->where('ward_no', $wardNo)->get();

            $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData);
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
     * AJAX — paginated data (client-side refresh without full page reload)
     */
    public function getPaginatedData($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas = DB::table("point_data_{$wardId}")->get();
        $misData = DB::table("mis_{$corp}")->where('ward_no', $wardNo)->get();

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData);
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

        $polygons = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas = DB::table("point_data_{$wardId}")->get();
        $misData = DB::table("mis_{$corp}")->where('ward_no', $wardNo)->get();

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Variation');

        $headers = [
            'S.No', 'GIS ID', 'Building Usage', 'Building Area (sqft)',
            'Assessment Usage', 'Assessment Area (sqft)',
            'Area Variation (sqft)', 'Variation %',
            'Area Status', 'Usage Status',
            'Floor Count', 'Basement', 'Percentage',
            'Assessment Count', 'Assessment Type',
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
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    /**
     * Export ward-wide summary PDF
     */
    public function exportPdffile($wardId, Request $request)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons = DB::table("polygons_{$wardId}")->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->get();
        $pointDatas = DB::table("point_data_{$wardId}")->get();
        $misData = DB::table("mis_{$corp}")->where('ward_no', $wardNo)->get();

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData);
        $buildingVariations = $this->applyFilters($buildingVariations, $request);

        $pdf = PDF::loadView('variation.pdf-export', [
            'buildingVariations' => $buildingVariations,
            'ward' => $ward,
            'zone' => $zone,
            'date' => now()->format('d-m-Y H:i:s'),
        ]);

        return $pdf->download("data_variation_ward_{$wardId}_" . date('Y-m-d_H-i-s') . ".pdf");
    }

    /**
     * Export single-building FORM 2 PDF
     */
    public function exportSinglePdf($wardId, $gisid)
    {
        $ward = Ward::findOrFail($wardId);
        $zone = Zone::findOrFail($ward->zone_id);
        $corp = $zone->corp_id;
        $wardNo = $ward->ward_no;

        $polygons = DB::table("polygons_{$wardId}")->where('gisid', $gisid)->get();
        $polygonDatas = DB::table("polygon_data_{$wardId}")->where('gisid', $gisid)->get();
        $pointDatas = DB::table("point_data_{$wardId}")->where('point_gisid', $gisid)->get();
        $misData = DB::table("mis_{$corp}")->where('ward_no', $wardNo)->get();

        $buildingVariations = $this->buildBuildingData($polygons, $polygonDatas, $pointDatas, $misData);
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


}
