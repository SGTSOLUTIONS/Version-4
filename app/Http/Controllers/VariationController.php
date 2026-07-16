<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ward;
use App\Models\Zone;
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

            if ($polyData) {
                $numberFloor = floatval($polyData->number_floor ?? 0);
                $basement = floatval($polyData->basement ?? 0);

                $buildingArea = ($numberFloor > 0 ? $numberFloor : 1) * $polygonSqfeet;

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
        $filtered = array_filter($allVariations, function($item) use ($request) {
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
            'usage_match' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'MATCH'; })),
            'usage_variation' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'VARIATION'; })),
            'usage_partial' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'PARTIAL_MATCH'; })),
            'usage_building_only' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'BUILDING_ONLY'; })),
            'usage_assessment_only' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'ASSESSMENT_ONLY'; })),
            'usage_no_data' => count(array_filter($filtered, function($v) { return $v['usage_status'] == 'NO_DATA'; })),
            'area_match' => count(array_filter($filtered, function($v) { return $v['area_status'] == 'MATCH'; })),
            'area_variation' => count(array_filter($filtered, function($v) { return $v['area_status'] == 'VARIATION'; })),
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
        $filtered = array_filter($allVariations, function($item) use ($request) {
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
}
