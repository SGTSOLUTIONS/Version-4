<?php
// app/Http/Controllers/InfrastructureController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class InfrastructureController extends Controller
{
    public function fetchInfrastructure($wardId)
    {
        $ward = \App\Models\Ward::findOrFail($wardId);
        // Get ward boundary from ward data
        $boundary = $this->getWardBoundary($ward);

        // Prepare the Python script execution
        $pythonScript = public_path('scripts/fetch_infrastructure_qgis.py');
        $outputDir = public_path('data/infrastructure/ward_' . $wardId);

        // Create temporary boundary file
        $boundaryFile = storage_path("app/temp/boundary_{$wardId}.geojson");
        File::ensureDirectoryExists(dirname($boundaryFile));
        file_put_contents($boundaryFile, json_encode($boundary));

        // Execute Python script
        $command = [
            'python3',
            $pythonScript,
            '--boundary',
            $boundaryFile,
            '--output',
            $outputDir
        ];

        $process = Process::command($command);
        $result = $process->run();

        // Clean up temp file
        File::delete($boundaryFile);

        if ($result->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Infrastructure data fetched successfully',
                'output' => $result->output(),
                'data_path' => $outputDir
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch infrastructure data',
                'error' => $result->errorOutput()
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
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$ward->center_lon - 0.01, $ward->center_lat - 0.01],
                [$ward->center_lon + 0.01, $ward->center_lat - 0.01],
                [$ward->center_lon + 0.01, $ward->center_lat + 0.01],
                [$ward->center_lon - 0.01, $ward->center_lat + 0.01],
                [$ward->center_lon - 0.01, $ward->center_lat - 0.01]
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
}
