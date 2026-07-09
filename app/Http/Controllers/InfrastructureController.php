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
     * Generate summary counts for a ward directly from infrastructure.geojson.
     */
    public function getInfrastructureSummary($wardId)
    {
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
     * Serve a single feature-type GeoJSON, filtered directly from
     * infrastructure.geojson by properties.type.
     */
    public function getFeatureByType($wardId, $type)
    {
        $dataPath = public_path("data/infrastructure/ward_{$wardId}/infrastructure.geojson");

        if (!File::exists($dataPath)) {
            return response()->json([
                'success' => false,
                'message' => 'No infrastructure data found for this ward'
            ], 404);
        }

        $data = json_decode(File::get($dataPath), true);
        $allFeatures = $data['features'] ?? [];

        $filtered = array_values(array_filter($allFeatures, function ($feature) use ($type) {
            return strtolower($feature['properties']['type'] ?? '') === strtolower($type);
        }));

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => count($filtered),
            'data' => [
                'type' => 'FeatureCollection',
                'features' => $filtered
            ]
        ]);
    }
}
