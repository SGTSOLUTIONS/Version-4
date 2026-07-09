<?php
// app/Http/Controllers/InfrastructureController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InfrastructureController extends Controller
{
    public function fetchInfrastructure($wardId)
    {
        try {
            $ward = \App\Models\Ward::findOrFail($wardId);

            // Get ward boundary from ward data
            $boundary = $this->getWardBoundary($ward);

            // Prepare the Python script execution
            $pythonScript = public_path('scripts/fetch_infrastructure_qgis.py');
            $outputDir = public_path('data/infrastructure/ward_' . $wardId);

            // Check if Python script exists
            if (!File::exists($pythonScript)) {
                Log::error("Python script not found at: " . $pythonScript);
                return response()->json([
                    'success' => false,
                    'message' => 'Python script not found',
                    'error' => "Script not found at: {$pythonScript}"
                ], 500);
            }

            // Ensure output directory exists
            if (!File::exists($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            // Create temporary boundary file
            $boundaryFile = storage_path("app/temp/boundary_{$wardId}.geojson");
            File::ensureDirectoryExists(dirname($boundaryFile));
            file_put_contents($boundaryFile, json_encode($boundary));

            // Check if Python3 is available
            $pythonCheck = Process::command(['which', 'python3'])->run();
            if (!$pythonCheck->successful()) {
                File::delete($boundaryFile);
                Log::error("Python3 not found in system PATH");
                return response()->json([
                    'success' => false,
                    'message' => 'Python3 is not available on the server',
                    'error' => 'Please install Python3 or check your PATH configuration'
                ], 500);
            }

            // Execute Python script using simple command
            $command = "python3 " . escapeshellarg($pythonScript) .
                       " --boundary " . escapeshellarg($boundaryFile) .
                       " --output " . escapeshellarg($outputDir) .
                       " 2>&1";

            Log::info("Executing command: " . $command);

            $process = Process::timeout(180)->command($command);
            $result = $process->run();

            // Clean up temp file
            File::delete($boundaryFile);

            if ($result->successful()) {
                // Check if summary file was created
                $summaryPath = $outputDir . '/summary.json';
                if (File::exists($summaryPath)) {
                    $summary = json_decode(File::get($summaryPath), true);
                    return response()->json([
                        'success' => true,
                        'message' => 'Infrastructure data fetched successfully',
                        'summary' => $summary,
                        'data_path' => $outputDir
                    ]);
                } else {
                    Log::warning("Summary file not found at: " . $summaryPath);
                    return response()->json([
                        'success' => true,
                        'message' => 'Infrastructure data fetched but summary not generated',
                        'output' => $result->output(),
                        'data_path' => $outputDir
                    ]);
                }
            } else {
                Log::error('Infrastructure fetch failed', [
                    'ward_id' => $wardId,
                    'error_output' => $result->errorOutput(),
                    'exit_code' => $result->exitCode(),
                    'command' => $command
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch infrastructure data',
                    'error' => $result->errorOutput(),
                    'command' => $command
                ], 500);
            }

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
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => ['ward_id' => $ward->id],
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => json_decode($ward->boundary_coordinates, true)
                        ]
                    ]
                ]
            ];
        }

        // If no boundary stored, create a default boundary around ward center
        $centerLat = $ward->center_lat ?? 19.0760;
        $centerLon = $ward->center_lon ?? 72.8777;

        return [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['ward_id' => $ward->id],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [$centerLon - 0.01, $centerLat - 0.01],
                            [$centerLon + 0.01, $centerLat - 0.01],
                            [$centerLon + 0.01, $centerLat + 0.01],
                            [$centerLon - 0.01, $centerLat + 0.01],
                            [$centerLon - 0.01, $centerLat - 0.01]
                        ]]
                    ]
                ]
            ]
        };
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

    public function testPython()
    {
        try {
            // Test if Python3 is available
            $testCommand = "python3 -c 'import sys; print(sys.version)' 2>&1";
            $process = Process::command($testCommand)->run();

            if ($process->successful()) {
                return response()->json([
                    'success' => true,
                    'python_version' => trim($process->output()),
                    'message' => 'Python3 is available'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Python3 is not available',
                    'error' => $process->errorOutput()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test Python',
                'error' => $e->getMessage()
            ]);
        }
    }
}
