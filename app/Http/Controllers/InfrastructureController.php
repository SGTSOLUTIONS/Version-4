<?php
// app/Http/Controllers/InfrastructureController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\InfrastructureFetcher;

class InfrastructureController extends Controller
{

    public function fetchInfrastructure($wardId)
    {
        try {
            $ward = \App\Models\Ward::findOrFail($wardId);
            $boundary = $this->getWardBoundary($ward);

            $outputDir = public_path('data/infrastructure/ward_' . $wardId);

            $fetcher = new InfrastructureFetcher($outputDir);
            $results = $fetcher->fetchAllInfrastructure($boundary);

            $fetcher->saveToGeojson($results);
            $fetcher->saveByType($results);
            $summary = $fetcher->createSummary($results);

            return response()->json([
                'success' => true,
                'message' => 'Infrastructure data fetched successfully',
                'summary' => $summary,
                'data_path' => $outputDir
            ]);
        } catch (\Exception $e) {
            Log::error("Infrastructure fetch error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch infrastructure data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getWardBoundary($ward)
    {
        if (
            !empty($ward->extent_left) && !empty($ward->extent_right) &&
            !empty($ward->extent_bottom) && !empty($ward->extent_top)
        ) {
            $left   = (float) $ward->extent_left;
            $right  = (float) $ward->extent_right;
            $bottom = (float) $ward->extent_bottom;
            $top    = (float) $ward->extent_top;

            // Detect if these are already lat/lon (EPSG:4326) or Web Mercator (EPSG:3857)
            $isLatLon = $left > -180 && $left < 180 && $bottom > -90 && $bottom < 90;

            if (!$isLatLon) {
                [$left, $bottom]  = $this->mercatorToLatLon($left, $bottom);
                [$right, $top]    = $this->mercatorToLatLon($right, $top);
            }

            return [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$left, $bottom],
                    [$right, $bottom],
                    [$right, $top],
                    [$left, $top],
                    [$left, $bottom],
                ]]
            ];
        }

        // Fallback 1: stored boundary column
        if (!empty($ward->boundary)) {
            $boundary = is_array($ward->boundary)
                ? $ward->boundary
                : json_decode($ward->boundary, true);

            if (isset($boundary['type']) && isset($boundary['coordinates'])) {
                return $boundary;
            }
            if (is_array($boundary)) {
                return [
                    'type' => 'Polygon',
                    'coordinates' => $boundary
                ];
            }
        }

        // Fallback 2: dummy box around center point
        $centerLat = $ward->center_lat ?? 19.0760;
        $centerLon = $ward->center_lon ?? 72.8777;

        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$centerLon - 0.01, $centerLat - 0.01],
                [$centerLon + 0.01, $centerLat - 0.01],
                [$centerLon + 0.01, $centerLat + 0.01],
                [$centerLon - 0.01, $centerLat + 0.01],
                [$centerLon - 0.01, $centerLat - 0.01]
            ]]
        ];
    }

    /**
     * Convert EPSG:3857 (Web Mercator) x/y to EPSG:4326 (lon/lat)
     */
    private function mercatorToLatLon($x, $y)
    {
        $lon = ($x / 20037508.34) * 180;
        $lat = (M_PI / 2) - (2 * atan(exp(-$y / 6378137.0)));
        $lat = $lat * 180 / M_PI;

        return [$lon, $lat];
    }
    public function getInfrastructureData($wardId)
    {
        $dataPath = public_path("data/infrastructure/ward_{$wardId}/infrastructure.geojson");

        if (!File::exists($dataPath)) {
            return $this->fetchInfrastructure($wardId);   // fresh fetch — only if file missing
        }

        $data = json_decode(File::get($dataPath), true);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getInfrastructureSummary($wardId)
    {
        $summaryPath = public_path("data/infrastructure/ward_{$wardId}/summary.json");

        if (!File::exists($summaryPath)) {
            return response()->json([
                'success' => false,
                'message' => 'No infrastructure data found for this ward'
            ], 404);
        }

        $summary = json_decode(File::get($summaryPath), true);

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    public function getFeatureByType($wardId, $type)
    {
        $filename = strtolower(str_replace(' ', '_', $type)) . '.geojson';
        $filePath = public_path("data/infrastructure/ward_{$wardId}/{$filename}");

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => "No data found for feature type: {$type}"
            ], 404);
        }

        $data = json_decode(File::get($filePath), true);

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => count($data['features'] ?? []),
            'data' => $data
        ]);
    }

    public function refreshInfrastructure($wardId)
    {
        $outputDir = public_path("data/infrastructure/ward_{$wardId}");
        if (File::exists($outputDir)) {
            File::deleteDirectory($outputDir);
        }

        return $this->fetchInfrastructure($wardId);
    }
    public function testPython()
    {
        try {
            $whichPython = Process::command(['which', 'python3'])->run();
            $pythonPath = trim($whichPython->output());

            $versionProcess = Process::command([$pythonPath ?: 'python3', '--version'])->run();

            $requestsCheck = Process::command([
                $pythonPath ?: 'python3',
                '-c',
                'import requests; print(requests.__version__)'
            ])->run();

            return response()->json([
                'success' => true,
                'which_python3' => $pythonPath,
                'python_version' => trim($versionProcess->output() . $versionProcess->errorOutput()),
                'requests_available' => $requestsCheck->successful(),
                'requests_version_or_error' => $requestsCheck->successful()
                    ? trim($requestsCheck->output())
                    : trim($requestsCheck->errorOutput()),
                'whoami' => trim(Process::command(['whoami'])->run()->output()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test Python',
                'error' => $e->getMessage()
            ]);
        }
    }
}
