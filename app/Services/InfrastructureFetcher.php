<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InfrastructureFetcher
{
    protected string $outputDir;

    // type => [tags, geometry]
    protected array $infrastructureTypes = [
        'Road' => ['tags' => ['highway' => ['primary','secondary','tertiary','residential','unclassified','service']], 'geometry' => 'LineString'],
        'Road Junction' => ['tags' => ['junction' => ['yes','roundabout']], 'geometry' => 'Point'],
        'Bus Stop' => ['tags' => ['highway' => ['bus_stop']], 'geometry' => 'Point'],
        'Traffic Signal' => ['tags' => ['highway' => ['traffic_signals']], 'geometry' => 'Point'],
        'Bridge' => ['tags' => ['bridge' => ['yes','viaduct']], 'geometry' => 'LineString'],
        'Drainage Line' => ['tags' => ['waterway' => ['ditch','drain']], 'geometry' => 'LineString'],
        'Storm Water Line' => ['tags' => ['waterway' => ['stormwater']], 'geometry' => 'LineString'],
        'Sewer Line' => ['tags' => ['man_made' => ['pipeline'], 'pipeline' => ['sewer']], 'geometry' => 'LineString'],
        'Water Supply Line' => ['tags' => ['man_made' => ['pipeline'], 'pipeline' => ['water']], 'geometry' => 'LineString'],
        'Waterbody' => ['tags' => ['natural' => ['water'], 'water' => ['pond','lake','reservoir']], 'geometry' => 'Polygon'],
        'Canal' => ['tags' => ['waterway' => ['canal']], 'geometry' => 'LineString'],
        'Culvert' => ['tags' => ['waterway' => ['culvert']], 'geometry' => 'LineString'],
        'Fire Hydrant' => ['tags' => ['emergency' => ['fire_hydrant']], 'geometry' => 'Point'],
        'Water Valve' => ['tags' => ['man_made' => ['valve']], 'geometry' => 'Point'],
        'Street Light' => ['tags' => ['highway' => ['street_lamp']], 'geometry' => 'Point'],
        'Electric Pole' => ['tags' => ['power' => ['pole']], 'geometry' => 'Point'],
        'Street Manhole' => ['tags' => ['man_made' => ['manhole']], 'geometry' => 'Point'],
        'Transformer' => ['tags' => ['power' => ['transformer']], 'geometry' => 'Point'],
        'Building' => ['tags' => ['building' => ['yes']], 'geometry' => 'Polygon'],
        'Boundary Wall' => ['tags' => ['barrier' => ['wall']], 'geometry' => 'LineString'],
        'Park' => ['tags' => ['leisure' => ['park']], 'geometry' => 'Polygon'],
        'Playground' => ['tags' => ['leisure' => ['playground']], 'geometry' => 'Polygon'],
        'Cemetery' => ['tags' => ['landuse' => ['cemetery']], 'geometry' => 'Polygon'],
        'Tree' => ['tags' => ['natural' => ['tree']], 'geometry' => 'Point'],
    ];

    protected array $colors = [
        'Road' => '#FF6B6B', 'Road Junction' => '#FFB74D', 'Bus Stop' => '#FFA726',
        'Traffic Signal' => '#F44336', 'Bridge' => '#8D6E63', 'Drainage Line' => '#4FC3F7',
        'Storm Water Line' => '#4DD0E1', 'Sewer Line' => '#9575CD', 'Water Supply Line' => '#4DB6AC',
        'Waterbody' => '#29B6F6', 'Canal' => '#0288D1', 'Culvert' => '#00897B',
        'Fire Hydrant' => '#EF5350', 'Water Valve' => '#81C784', 'Street Light' => '#FFD54F',
        'Electric Pole' => '#FF8A65', 'Street Manhole' => '#A1887F', 'Transformer' => '#AB47BC',
        'Building' => '#78909C', 'Boundary Wall' => '#795548', 'Park' => '#66BB6A',
        'Playground' => '#AED581', 'Cemetery' => '#A1887F', 'Tree' => '#388E3C',
    ];

    public function __construct(string $outputDir)
    {
        $this->outputDir = rtrim($outputDir, '/');
        File::ensureDirectoryExists($this->outputDir);
    }

    protected function getBboxFromPolygon(array $coordinates): array
    {
        $lats = array_map(fn($c) => $c[1], $coordinates);
        $lons = array_map(fn($c) => $c[0], $coordinates);

        return [
            'min_lat' => min($lats) - 0.001,
            'max_lat' => max($lats) + 0.001,
            'min_lon' => min($lons) - 0.001,
            'max_lon' => max($lons) + 0.001,
        ];
    }

    protected function buildOverpassQuery(array $boundary, array $config): string
    {
        $tagFilters = '';
        foreach ($config['tags'] as $key => $values) {
            foreach ((array) $values as $value) {
                $tagFilters .= "[\"{$key}\"=\"{$value}\"]";
            }
        }

        $osmType = in_array($config['geometry'], ['LineString', 'Polygon']) ? 'way' : 'node';

        if (($boundary['type'] ?? null) === 'Polygon') {
            $coords = $boundary['coordinates'][0];
            $bbox = $this->getBboxFromPolygon($coords);
            $bboxStr = "{$bbox['min_lat']},{$bbox['min_lon']},{$bbox['max_lat']},{$bbox['max_lon']}";
        } else {
            $bboxStr = "19.0,72.8,19.1,72.9"; // fallback default
        }

        return "[out:json][timeout:60];({$osmType}{$tagFilters}({$bboxStr}););out body;>;out skel qt;";
    }

    protected function extractWayGeometry(array $way, array $nodes): ?array
    {
        $coordinates = [];
        foreach (($way['nodes'] ?? []) as $ref) {
            if (isset($nodes[$ref])) {
                $coordinates[] = $nodes[$ref];
            }
        }

        if (count($coordinates) < 2) {
            return null;
        }

        $isClosed = $coordinates[0] === end($coordinates) && count($coordinates) > 3;

        return $isClosed
            ? ['type' => 'Polygon', 'coordinates' => [$coordinates]]
            : ['type' => 'LineString', 'coordinates' => $coordinates];
    }

    protected function processOsmResponse(array $data, string $featureType): array
    {
        $features = [];
        $elements = $data['elements'] ?? [];

        $nodes = [];
        foreach ($elements as $el) {
            if (($el['type'] ?? null) === 'node') {
                $nodes[$el['id']] = [$el['lon'], $el['lat']];
            }
        }

        foreach ($elements as $el) {
            if (($el['type'] ?? null) !== 'way') {
                continue;
            }

            $geometry = $this->extractWayGeometry($el, $nodes);
            if (!$geometry) {
                continue;
            }

            $tags = $el['tags'] ?? [];

            $properties = [
                'name' => $tags['name'] ?? '',
                'description' => $tags['description'] ?? '',
                'osm_id' => $el['id'],
            ];

            if ($featureType === 'Building') {
                $properties['building_type'] = $tags['building'] ?? '';
                $properties['height'] = $tags['height'] ?? '';
                $properties['levels'] = $tags['building:levels'] ?? '';
            } elseif ($featureType === 'Road') {
                $properties['road_type'] = $tags['highway'] ?? '';
                $properties['name'] = $tags['name'] ?? '';
                $properties['speed_limit'] = $tags['maxspeed'] ?? '';
            } elseif ($featureType === 'Waterbody') {
                $properties['water_type'] = $tags['water'] ?? 'pond';
                $properties['name'] = $tags['name'] ?? '';
            }

            $features[] = [
                'type' => $featureType,
                'id' => $el['id'],
                'tags' => $tags,
                'geometry' => $geometry,
                'properties' => $properties,
            ];
        }

        return $features;
    }

    protected function fetchOsmData(array $boundary, string $featureType, array $config): array
    {
        $query = $this->buildOverpassQuery($boundary, $config);

        try {
            $response = Http::timeout(120)
                ->asForm()
                ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

            if ($response->successful()) {
                return $this->processOsmResponse($response->json(), $featureType);
            }

            Log::warning("Overpass error fetching {$featureType}: HTTP {$response->status()}");
            return [];
        } catch (\Exception $e) {
            Log::warning("Overpass exception fetching {$featureType}: " . $e->getMessage());
            return [];
        }
    }

    public function fetchAllInfrastructure(array $boundary): array
    {
        $results = [];

        foreach ($this->infrastructureTypes as $featureType => $config) {
            $results[$featureType] = $this->fetchOsmData($boundary, $featureType, $config);
            usleep(300000); // small delay, be nice to Overpass (0.3s)
        }

        return $results;
    }

    public function saveToGeojson(array $results, string $filename = 'infrastructure.geojson'): string
    {
        $allFeatures = [];

        foreach ($results as $featureType => $features) {
            $color = $this->colors[$featureType] ?? '#999999';

            foreach ($features as $feature) {
                $allFeatures[] = [
                    'type' => 'Feature',
                    'geometry' => $feature['geometry'],
                    'properties' => array_merge(
                        ['type' => $featureType, 'color' => $color, 'osm_id' => $feature['id']],
                        $feature['properties']
                    ),
                ];
            }
        }

        $geojson = ['type' => 'FeatureCollection', 'features' => $allFeatures];
        $path = "{$this->outputDir}/{$filename}";
        File::put($path, json_encode($geojson, JSON_PRETTY_PRINT));

        return $path;
    }

    public function saveByType(array $results): void
    {
        foreach ($results as $featureType => $features) {
            if (empty($features)) {
                continue;
            }

            $geojson = [
                'type' => 'FeatureCollection',
                'features' => array_map(fn($f) => [
                    'type' => 'Feature',
                    'geometry' => $f['geometry'],
                    'properties' => array_merge(['osm_id' => $f['id']], $f['properties']),
                ], $features),
            ];

            $filename = strtolower(str_replace(' ', '_', $featureType)) . '.geojson';
            File::put("{$this->outputDir}/{$filename}", json_encode($geojson, JSON_PRETTY_PRINT));
        }
    }

    public function createSummary(array $results): array
    {
        $counts = [];
        $total = 0;
        foreach ($results as $type => $features) {
            if (!empty($features)) {
                $counts[$type] = count($features);
                $total += count($features);
            }
        }

        $summary = [
            'total_features' => $total,
            'feature_counts' => $counts,
            'feature_types' => array_keys($results),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        File::put("{$this->outputDir}/summary.json", json_encode($summary, JSON_PRETTY_PRINT));

        return $summary;
    }
}
