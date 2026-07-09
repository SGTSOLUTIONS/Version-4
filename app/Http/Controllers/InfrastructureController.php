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
        // Get boundary from ward data
        if ($ward->boundary_coordinates) {
            return [
                'type' => 'Polygon',
                'coordinates' => json_decode($ward->boundary_coordinates, true)
            ];
        }

        // If no boundary stored, create a default boundary around ward center
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

    public function getInfrastructureData($wardId)
    {
        $dataPath = public_path("data/infrastructure/ward_{$wardId}/infrastructure.geojson");

        if (!File::exists($dataPath)) {
            // If data doesn't exist, try to fetch it
            return $this->fetchInfrastructure($wardId);
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

    /**
     * Diagnostic route: confirms which python3 PHP sees,
     * its version, and whether `requests` is importable.
     */
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
