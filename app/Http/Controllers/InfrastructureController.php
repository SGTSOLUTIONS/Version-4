<?php
// app/Http/Controllers/InfrastructureController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class InfrastructureController extends Controller
{
    /**
     * Serve the full infrastructure GeoJSON for a ward.
     * File must be manually placed at:
     * public/data/infrastructure/ward_{wardId}/infrastructure.geojson
     */
    public function getInfrastructureData($wardId)
    {
        $dataPath = public_path("data/infrastructure/ward_{$wardId}/infrastructure.geojson");

        if (!File::exists($dataPath)) {
            return response()->json([
                'success' => false,
                'message' => 'No infrastructure file found for this ward. Please upload infrastructure.geojson to public/data/infrastructure/ward_' . $wardId . '/'
            ], 404);
        }

        $data = json_decode(File::get($dataPath), true);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Serve summary counts for a ward (used by dashboard stat card).
     * File must be manually placed at:
     * public/data/infrastructure/ward_{wardId}/summary.json
     * OR this will auto-generate it from infrastructure.geojson if missing.
     */
    public function getInfrastructureSummary($wardId)
    {
        $summaryPath = public_path("data/infrastructure/ward_{$wardId}/summary.json");

        if (File::exists($summaryPath)) {
            $summary = json_decode(File::get($summaryPath), true);
            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        }

        // Auto-generate summary from infrastructure.geojson if summary.json wasn't provided
        $dataPath = public_path("data/infrastructure/ward_{$wardId}/infrastructure.geojson");

        if (!File::exists($dataPath)) {
            return response()->json([
                'success' => false,
                'message' => 'No infrastructure data found for this ward'
            ], 404);
        }

        $data = json_decode(File::get($dataPath), true);
        $features = $data['features'] ?? [];

        $counts = [];
        foreach ($features as $feature) {
            $type = $feature['properties']['type'] ?? 'Unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        $summary = [
            'total_features' => count($features),
            'feature_counts' => $counts,
            'feature_types' => array_keys($counts),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    /**
     * Serve a single feature-type GeoJSON (optional — only works if you
     * also upload per-type files like road.geojson, building.geojson, etc.)
     */
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
