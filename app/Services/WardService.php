<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WardService
{
    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Create all ward-specific tables
    // ─────────────────────────────────────────────────────────────

    public function createWardTables($wardId): array
    {
        return [
            'polygon'      => $this->createPolygonTable($wardId),
            'line'         => $this->createLineTable($wardId),
            'point'        => $this->createPointTable($wardId),
            'polygon_data' => $this->createPolygonDataTable($wardId),
            'point_data'   => $this->createPointDataTable($wardId),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Upsert polygon + point data from a GeoJSON file
    // ─────────────────────────────────────────────────────────────

    public function createPolygonUpdate(
        string $polygonTable,
        string $pointTable,
        $file,
        $useTransaction = true
    ): array {
        set_time_limit(600);

        try {
            $geoJsonContent = file_get_contents($file->getRealPath());
            $geoData        = json_decode($geoJsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid GeoJSON format: ' . json_last_error_msg());
            }

            if (empty($geoData['features']) || !is_array($geoData['features'])) {
                throw new \Exception('GeoJSON missing or empty "features" key.');
            }

            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            foreach ($geoData['features'] as $feature) {
                // ✅ Geometry-ல் இருந்து type-ஐ எடுங்கள் (properties-ல் இருந்து அல்ல)
                $geometryType = $feature['geometry']['type']        ?? null;
                $coords       = $feature['geometry']['coordinates'] ?? null;

                if (!$geometryType || !$coords) {
                    continue;
                }

                if (!in_array($geometryType, ['Polygon', 'MultiPolygon'])) {
                    continue;
                }

                // GIS ID variations
                $gisid = $feature['properties']['GIS_ID']
                    ?? $feature['properties']['gisid']
                    ?? $feature['properties']['GisId']
                    ?? $feature['properties']['GISID']
                    ?? $this->checkGISID($polygonTable)
                    ?? uniqid('GIS_');

                // ✅ Full coordinates-ஐ area calculation-க்கு pass செய்யுங்கள்
                $sqfeet = $this->calculatePolygonAreaInSquareFeet($coords);

                // ✅ Full coordinates-ஐ store செய்யுங்கள் (flatten செய்யாதீர்கள்!)
                $polygonData = [
                    'type'        => $geometryType,  // "Polygon" அல்லது "MultiPolygon"
                    'coordinates' => json_encode($coords, JSON_UNESCAPED_UNICODE),
                    'sqfeet'      => (string) $sqfeet,
                    'updated_at'  => now(),
                ];

                $polygonExists = DB::table($polygonTable)
                    ->where('gisid', $gisid)
                    ->exists();

                if ($polygonExists) {
                    DB::table($polygonTable)
                        ->where('gisid', $gisid)
                        ->update($polygonData);
                } else {
                    DB::table($polygonTable)->insert(
                        array_merge(['gisid' => $gisid, 'created_at' => now()], $polygonData)
                    );
                }

                // Midpoint-க்கு மட்டும் representative ring use செய்யுங்கள்
                $representativeRing = $this->flattenCoordinates($geometryType, $coords);
                $midpoint = $this->calculateMidpoint($representativeRing);

                if ($midpoint) {
                    $pointData = [
                        'type'        => 'Point',
                        'coordinates' => json_encode($midpoint),
                        'updated_at'  => now(),
                    ];

                    $pointExists = DB::table($pointTable)
                        ->where('gisid', $gisid)
                        ->exists();

                    if ($pointExists) {
                        DB::table($pointTable)
                            ->where('gisid', $gisid)
                            ->update($pointData);
                    } else {
                        DB::table($pointTable)->insert(
                            array_merge(['gisid' => $gisid, 'created_at' => now()], $pointData)
                        );
                    }
                }
            }

            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'Polygon and Point data updated successfully.',
            ];
        } catch (\Exception $e) {
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('Polygon Update Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkGISID($polygonTable)
    {
        $allIds = DB::table($polygonTable)->pluck('gisid');
        $maxNumber = 0;
        $prefix = 'GIS_';

        foreach ($allIds as $id) {
            if (preg_match_all('/\d+/', $id, $matches)) {
                $numbers = $matches[0];
                $lastNum = (int)end($numbers);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                    $pos = strrpos($id, (string)$lastNum);
                    if ($pos !== false) {
                        $prefix = substr($id, 0, $pos);
                    }
                }
            }
        }

        $newGisNumber = $maxNumber + 1;
        return $prefix . $newGisNumber;
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Upsert line data from a GeoJSON file
    // ─────────────────────────────────────────────────────────────

    public function createLineUpdate(
        string $lineTable,
        $file,
        $useTransaction = true
    ): array {
        set_time_limit(600);

        try {
            $geoJsonContent = file_get_contents($file->getRealPath());
            $geoData        = json_decode($geoJsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid GeoJSON format: ' . json_last_error_msg());
            }

            if (empty($geoData['features']) || !is_array($geoData['features'])) {
                throw new \Exception('GeoJSON missing or empty "features" key.');
            }

            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            foreach ($geoData['features'] as $feature) {
                $geometryType = $feature['geometry']['type']        ?? null;
                $coords       = $feature['geometry']['coordinates'] ?? null;

                if (!$geometryType || !$coords) {
                    continue;
                }

                if (!in_array($geometryType, ['LineString', 'MultiLineString'])) {
                    continue;
                }

                $gisid = $feature['properties']['GIS_ID']
                    ?? $feature['properties']['gisid']
                    ?? $feature['properties']['GisId']
                    ?? $feature['properties']['GISID']
                    ?? uniqid('GIS_');

                $roadName = $feature['properties']['road_name']
                    ?? $feature['properties']['ROAD_NAME']
                    ?? $feature['properties']['RoadName']
                    ?? null;

                $pincode = $feature['properties']['pincode']
                    ?? $feature['properties']['PINCODE']
                    ?? $feature['properties']['Pincode']
                    ?? null;

                $lineData = [
                    'type'        => $geometryType,
                    'coordinates' => json_encode($coords, JSON_UNESCAPED_UNICODE),
                    'road_name'   => $roadName,
                    'pincode'     => $pincode,
                    'updated_at'  => now(),
                ];

                $exists = DB::table($lineTable)
                    ->where('gisid', $gisid)
                    ->exists();

                if ($exists) {
                    DB::table($lineTable)
                        ->where('gisid', $gisid)
                        ->update($lineData);
                } else {
                    DB::table($lineTable)->insert(
                        array_merge(['gisid' => $gisid, 'created_at' => now()], $lineData)
                    );
                }
            }

            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'Line data updated successfully.',
            ];
        } catch (\Exception $e) {
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('Line Update Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Drop all ward-specific tables
    // ─────────────────────────────────────────────────────────────

    public function dropWardTables($wardId): bool
    {
        $tables = [
            'polygons_'     . $wardId,
            'lines_'        . $wardId,
            'points_'       . $wardId,
            'polygon_data_' . $wardId,
            'point_data_'   . $wardId,
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
                Log::info("Table dropped: {$table}");
            }
        }

        return true;
    }

    public function getWardTables($wardId): array
    {
        return [
            'polygon'      => 'polygons_'     . $wardId,
            'line'         => 'lines_'        . $wardId,
            'point'        => 'points_'       . $wardId,
            'polygon_data' => 'polygon_data_' . $wardId,
            'point_data'   => 'point_data_'   . $wardId,
        ];
    }

    public function checkWardTablesExist($wardId): array
    {
        $missingTables = [];

        foreach ($this->getWardTables($wardId) as $key => $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $key;
            }
        }

        return [
            'all_exist'      => empty($missingTables),
            'missing_tables' => $missingTables,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Create individual tables
    // ─────────────────────────────────────────────────────────────

    private function createPolygonTable($wardId): string
    {
        $table = 'polygons_' . $wardId;

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('gisid')->unique();
                $t->string('type')->default('Polygon');
                $t->json('coordinates')->nullable();
                $t->string('sqfeet')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
            Log::info("Polygon table created: {$table}");
        }

        return $table;
    }

    private function createLineTable($wardId): string
    {
        $table = 'lines_' . $wardId;

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('gisid')->unique();
                $t->string('type')->default('LineString');
                $t->json('coordinates')->nullable();
                $t->string('road_name')->nullable();
                $t->string('pincode')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
            Log::info("Line table created: {$table}");
        }

        return $table;
    }

    private function createPointTable($wardId): string
    {
        $table = 'points_' . $wardId;

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('gisid')->unique();
                $t->string('type')->default('Point');
                $t->json('coordinates')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
            Log::info("Point table created: {$table}");
        }

        return $table;
    }

    private function createPolygonDataTable($wardId): string
    {
        $table = 'polygon_data_' . $wardId;

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('gisid')->nullable();
                $t->string('number_bill')->nullable();
                $t->string('number_shop')->nullable();
                $t->string('number_floor')->nullable();
                $t->string('liftroom')->nullable();
                $t->string('headroom')->nullable();
                $t->string('overhead_tank')->nullable();
                $t->string('percentage')->nullable();
                $t->string('building_name')->nullable();
                $t->string('building_usage')->nullable();
                $t->string('construction_type')->nullable();
                $t->string('road_name')->nullable();
                $t->string('ugd')->nullable();
                $t->string('rainwater_harvesting')->nullable();
                $t->string('parking')->nullable();
                $t->string('ramp')->nullable();
                $t->string('hoarding')->nullable();
                $t->string('cctv')->nullable();
                $t->string('cell_tower')->nullable();
                $t->string('solar_panel')->nullable();
                $t->string('basement')->nullable();
                $t->string('water_connection')->nullable();
                $t->string('phone')->nullable();
                $t->string('building_type')->nullable();
                $t->string('image')->nullable();
                $t->string('image2')->nullable();
                $t->string('zone')->nullable();
                $t->string('worker_name')->nullable();
                $t->string('remarks')->nullable();
                $t->string('corporationremarks')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
            Log::info("Polygon data table created: {$table}");
        }

        return $table;
    }

    private function createPointDataTable($wardId): string
    {
        $polygonDataTable = 'polygon_data_' . $wardId;
        $table            = 'point_data_'   . $wardId;

        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) use ($polygonDataTable) {
                $t->id();
                $t->unsignedBigInteger('building_data_id')->nullable();
                $t->foreign('building_data_id')
                    ->references('id')
                    ->on($polygonDataTable)
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $t->string('assessment_type')->nullable();
                $t->string('point_gisid')->nullable();
                $t->string('worker_name')->nullable();
                $t->string('assessment')->nullable();
                $t->string('old_assessment')->nullable();
                $t->string('owner_name')->nullable();
                $t->string('present_owner_name')->nullable();
                $t->string('eb')->nullable();
                $t->string('floor')->nullable();
                $t->string('bill_usage')->nullable();
                $t->string('aadhar_no')->nullable();
                $t->string('ration_no')->nullable();
                $t->string('phone_number')->nullable();
                $t->string('old_door_no')->nullable();
                $t->string('new_door_no')->nullable();
                $t->string('remarks')->nullable();
                $t->string('plot_area')->nullable();
                $t->string('water_tax')->nullable();
                $t->string('halfyeartax')->nullable();
                $t->string('balance')->nullable();
                $t->string('no_of_persons')->nullable();
                $t->string('qcsqfeet')->nullable();
                $t->string('qcusage')->nullable();
                $t->string('qc_name')->nullable();
                $t->string('qc_remarks')->nullable();
                $t->string('zone')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
            Log::info("Point data table created: {$table}");
        }

        return $table;
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Export all roads for a ward
    // ─────────────────────────────────────────────────────────────

    public function exportAllRoads($ward_id)
    {
        try {
            $lineTable = "lines_" . $ward_id;

            if (!Schema::hasTable($lineTable)) {
                return response()->json([
                    "success" => false,
                    "message" => "Line table not found for ward #{$ward_id}"
                ], 404);
            }

            $allLines = DB::table($lineTable)->get();

            if ($allLines->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No lines found for ward #{$ward_id}"
                ], 404);
            }

            $features = [];

            foreach ($allLines as $line) {
                $coordinates = json_decode($line->coordinates, true);

                if (!$coordinates) {
                    continue;
                }

                $properties = [
                    "gisid" => $line->gisid,
                    "type"  => $line->type ?? 'LineString',
                ];

                if (isset($line->road_name) && !is_null($line->road_name)) {
                    $properties["road_name"] = $line->road_name;
                }

                if (isset($line->pincode) && !is_null($line->pincode)) {
                    $properties["pincode"] = $line->pincode;
                }

                $features[] = [
                    "type" => "Feature",
                    "properties" => $properties,
                    "geometry" => [
                        "type" => $line->type ?? 'LineString',
                        "coordinates" => $coordinates
                    ]
                ];
            }

            $geojson = [
                "type" => "FeatureCollection",
                "features" => $features
            ];

            $fileName = "all_lines_ward_{$ward_id}.geojson";

            return response()->streamDownload(function () use ($geojson) {
                echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }, $fileName, [
                'Content-Type' => 'application/geo+json',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "line"    => $e->getLine(),
                "file"    => $e->getFile(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Store single polygon
    // ─────────────────────────────────────────────────────────────

    public function storeSinglePolygon($data, $useTransaction = true)
    {
        try {
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            $tableName      = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_'   . $data['ward_id'];

            $feature = is_string($data['feature'])
                ? json_decode($data['feature'], true)
                : $data['feature'];

            // ✅ Full coordinates-ஐ area calculation-க்கு pass செய்யுங்கள்
            $sqfeet = $this->calculatePolygonAreaInSquareFeet($feature);

            // Representative ring for midpoint
            $layerType = $data['layer_type'] ?? 'Polygon';
            $representativeRing = $this->flattenCoordinates($layerType, $feature);
            $midpoint = $this->calculateMidpoint($representativeRing);

            $gisid = $this->checkGISID($tableName) ?? uniqid('GIS_');

            // ✅ Full coordinates store
            DB::table($tableName)->insert([
                'gisid'       => $gisid,
                'type'        => $layerType,
                'coordinates' => json_encode($feature, JSON_UNESCAPED_UNICODE),
                'sqfeet'      => (string) $sqfeet,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            if ($midpoint) {
                DB::table($pointTableName)->insert([
                    'gisid'       => $gisid,
                    'type'        => 'point',
                    'coordinates' => json_encode($midpoint),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'status'   => true,
                'gisid'    => $gisid,
                'message'  => 'Polygon stored successfully',
                'polygons' => DB::table($tableName)->get(),
                'points'   => DB::table($pointTableName)->get(),
            ];
        } catch (\Exception $e) {
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('storeSinglePolygon error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Store single line
    // ─────────────────────────────────────────────────────────────

    public function storeSingleLine($tableName, $file, $useTransaction = true)
    {
        try {
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            if (is_string($file)) {
                $geoJsonContent = file_get_contents($file);
            } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                $geoJsonContent = file_get_contents($file->getRealPath());
            } else {
                throw new \Exception('Invalid file parameter');
            }

            $geoJson = json_decode($geoJsonContent, true);

            if (!$geoJson || !isset($geoJson['type'])) {
                throw new \Exception('Invalid GeoJSON file format');
            }

            if (!Schema::hasTable($tableName)) {
                $wardId = (int) str_replace('lines_', '', $tableName);
                $this->createLineTable($wardId);
            }

            $processedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;
            $skippedCount = 0;
            $errors = [];

            if ($geoJson['type'] === 'FeatureCollection') {
                $features = $geoJson['features'];
            } elseif ($geoJson['type'] === 'Feature') {
                $features = [$geoJson];
            } else {
                throw new \Exception('Unsupported GeoJSON type: ' . $geoJson['type']);
            }

            if (empty($features)) {
                throw new \Exception('No features found in GeoJSON file');
            }

            foreach ($features as $index => $feature) {
                try {
                    if (!isset($feature['geometry']) || !isset($feature['geometry']['type'])) {
                        $skippedCount++;
                        continue;
                    }

                    $geometryType = $feature['geometry']['type'];

                    if ($geometryType !== 'LineString' && $geometryType !== 'MultiLineString') {
                        $skippedCount++;
                        continue;
                    }

                    $properties = $feature['properties'] ?? [];

                    $gisid = $properties['gisid']
                        ?? $properties['GIS_ID']
                        ?? $properties['id']
                        ?? $properties['ID']
                        ?? null;

                    $isUpdate = false;

                    if ($gisid) {
                        $existing = DB::table($tableName)->where('gisid', $gisid)->first();
                        if ($existing) {
                            $isUpdate = true;
                        }
                    }

                    $coordinates = $feature['geometry']['coordinates'];

                    $roadName = $properties['road_name']
                        ?? $properties['name']
                        ?? $properties['ROAD_NAME']
                        ?? $properties['road']
                        ?? $properties['RoadName']
                        ?? $properties['ROAD']
                        ?? null;

                    if ($isUpdate) {
                        DB::table($tableName)
                            ->where('gisid', $gisid)
                            ->update([
                                'type'        => $geometryType,
                                'coordinates' => json_encode($coordinates),
                                'road_name'   => $roadName ?? $existing->road_name,
                                'updated_at'  => now(),
                            ]);

                        $updatedCount++;
                    } else {
                        if (!$gisid) {
                            $gisid = $this->generateLineGISID($tableName);
                        }

                        DB::table($tableName)->insert([
                            'gisid'       => $gisid,
                            'type'        => $geometryType,
                            'coordinates' => json_encode($coordinates),
                            'road_name'   => $roadName,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);

                        $insertedCount++;
                    }

                    $processedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Feature #$index error: " . $e->getMessage();
                    Log::error('Feature processing error: ' . $e->getMessage());
                }
            }

            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'status'    => true,
                'message'   => "Processed $processedCount features successfully",
                'processed' => $processedCount,
                'inserted'  => $insertedCount,
                'updated'   => $updatedCount,
                'skipped'   => $skippedCount,
                'errors'    => $errors,
                'lines'     => DB::table($tableName)->get(),
                'table'     => $tableName
            ];
        } catch (\Exception $e) {
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('storeSingleLine error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ];
        }
    }

    public function createSingleLine(array $data)
    {
        try {
            $wardId      = $data['ward_id'];
            $layerType   = $data['layer_type'];
            $coordinates = $data['feature'];

            $tableName = 'lines_' . $wardId;

            if (!Schema::hasTable($tableName)) {
                $this->createLineTable($wardId);
            }

            $gisid = $this->generateLineGISID($tableName);

            if (is_string($coordinates)) {
                $coordinates = json_decode($coordinates, true);
            }

            $coordinates = [$coordinates];

            DB::table($tableName)->insert([
                'gisid'       => $gisid,
                'type'        => "MultiLineString",
                'coordinates' => json_encode($coordinates),
                'road_name'   => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return [
                'status'  => true,
                'gisid'   => $gisid,
                'message' => 'Line stored successfully',
                'lines'   => DB::table($tableName)->get()
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function generateLineGISID($tableName): string
    {
        $prefix = 'LINE_';
        $timestamp = time();
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $gisid = $prefix . $timestamp . '_' . $random;

        $exists = DB::table($tableName)->where('gisid', $gisid)->exists();

        if ($exists) {
            return $this->generateLineGISID($tableName);
        }

        return $gisid;
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Store split polygon result
    // ─────────────────────────────────────────────────────────────

    public function storeSplitPolygon(array $data, $useTransaction = true): array
    {
        $startedTransaction = false;

        try {
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            }

            $tableName      = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_'   . $data['ward_id'];

            $features = is_string($data['feature'])
                ? json_decode($data['feature'], true)
                : $data['feature'];

            if (empty($features) || count($features) < 2) {
                throw new \Exception('Split polygon must return at least two polygons.');
            }

            $originalGisid = $data['gisid'];

            $originalPolygon = DB::table($tableName)
                ->where('gisid', $originalGisid)
                ->first();

            if (!$originalPolygon) {
                throw new \Exception('Original polygon not found.');
            }

            foreach ($features as $index => $coords) {
                // Remove one extra array level if exists
                if (
                    isset($coords[0]) &&
                    is_array($coords[0]) &&
                    isset($coords[0][0]) &&
                    is_array($coords[0][0])
                ) {
                    $coords = $coords[0];
                }

                // ✅ Full ring coordinates for area
                $sqfeet = $this->calculatePolygonAreaInSquareFeet([$coords]);
                $midpoint = $this->calculateMidpoint($coords);

                if ($index == 0) {
                    DB::table($tableName)
                        ->where('gisid', $originalGisid)
                        ->update([
                            'coordinates' => json_encode($coords),
                            'sqfeet'      => (string) $sqfeet,
                            'updated_at'  => now(),
                        ]);

                    DB::table($pointTableName)->updateOrInsert(
                        ['gisid' => $originalGisid],
                        [
                            'type'        => 'point',
                            'coordinates' => json_encode($midpoint),
                            'updated_at'  => now(),
                            'created_at'  => now(),
                        ]
                    );
                } else {
                    $newGisid = $this->checkGISID($tableName);

                    DB::table($tableName)->insert([
                        'gisid'       => $newGisid,
                        'type'        => $originalPolygon->type,
                        'coordinates' => json_encode($coords),
                        'sqfeet'      => (string) $sqfeet,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    DB::table($pointTableName)->insert([
                        'gisid'       => $newGisid,
                        'type'        => 'point',
                        'coordinates' => json_encode($midpoint),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'status'   => true,
                'message'  => 'Polygon split successfully.',
                'polygons' => DB::table($tableName)->get(),
                'points'   => DB::table($pointTableName)->get(),
            ];
        } catch (\Exception $e) {
            if ($startedTransaction) {
                DB::rollBack();
            }

            Log::error('Split Polygon Error : ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

   public function storeUpdatePolygon($data, $useTransaction = true)
{
    $startedTransaction = false;

    try {

        if ($useTransaction && DB::transactionLevel() === 0) {
            DB::beginTransaction();
            $startedTransaction = true;
        }

        $tableName      = 'polygons_' . $data['ward_id'];
        $pointTableName = 'points_'   . $data['ward_id'];
        $pointDataTable = 'point_data_' . $data['ward_id'];

        $feature = is_string($data['feature'])
            ? json_decode($data['feature'], true)
            : $data['feature'];

        if (!is_array($feature) || empty($feature)) {
            throw new \Exception('Invalid polygon coordinates.');
        }

        $gisid = $data['gisid'];

        /*
        |--------------------------------------------------------------------------
        | Detect geometry type + coordinates
        |--------------------------------------------------------------------------
        */

        if (isset($feature['type'], $feature['coordinates'])) {
            // GeoJSON Geometry object
            $layerType   = $feature['type'];
            $coordinates = $feature['coordinates'];
        } else {
            // Raw coordinates — detect depth
            $depth = $this->coordinateDepth($feature);

            if ($depth >= 4) {
                $layerType   = 'MultiPolygon';
                $coordinates = $feature;
            } elseif ($depth === 3) {
                $layerType   = 'Polygon';
                $coordinates = $feature;
            } elseif ($depth === 2) {
                // Bare ring — wrap into Polygon
                $layerType   = 'Polygon';
                $coordinates = [$feature];
            } else {
                throw new \Exception('Unsupported coordinate structure.');
            }
        }

        if (!in_array($layerType, ['Polygon', 'MultiPolygon'], true)) {
            throw new \Exception("Unsupported polygon type: {$layerType}");
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate area using FULL coordinates
        |--------------------------------------------------------------------------
        */

        $sqfeet = $this->calculatePolygonAreaInSquareFeet($coordinates);

        /*
        |--------------------------------------------------------------------------
        | Pick representative ring for midpoint
        |   Polygon      -> outer ring of the polygon
        |   MultiPolygon -> outer ring of the FIRST polygon
        |--------------------------------------------------------------------------
        */

        $representativeRing = $this->getRepresentativeRing($layerType, $coordinates);

        if (!is_array($representativeRing) || count($representativeRing) < 3) {
            throw new \Exception(
                'Invalid polygon ring for midpoint. Type=' . $layerType .
                ' Ring=' . json_encode($representativeRing)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate midpoint (arithmetic centroid of the representative ring)
        |--------------------------------------------------------------------------
        */

        $midpoint = $this->calculateMidpoint($representativeRing);

        if (
            !$midpoint ||
            !isset($midpoint[0], $midpoint[1]) ||
            !is_numeric($midpoint[0]) ||
            !is_numeric($midpoint[1])
        ) {
            throw new \Exception('Invalid midpoint generated: ' . json_encode($midpoint));
        }

        /*
        |--------------------------------------------------------------------------
        | Find polygon
        |--------------------------------------------------------------------------
        */

        $existingPolygon = DB::table($tableName)
            ->where('gisid', $gisid)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Check point data
        |--------------------------------------------------------------------------
        */

        $hasPointData = false;

        if (Schema::hasTable($pointDataTable)) {
            $hasPointData = DB::table($pointDataTable)
                ->where('point_gisid', $gisid)
                ->exists();
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE EXISTING POLYGON
        |--------------------------------------------------------------------------
        */

        if ($existingPolygon) {

            DB::table($tableName)
                ->where('gisid', $gisid)
                ->update([
                    'type'        => $layerType,
                    'coordinates' => json_encode($coordinates, JSON_UNESCAPED_UNICODE),
                    'sqfeet'      => (string) $sqfeet,
                    'updated_at'  => now(),
                ]);

            $existingPoint = null;

            if (Schema::hasTable($pointTableName)) {
                $existingPoint = DB::table($pointTableName)
                    ->where('gisid', $gisid)
                    ->first();
            }

            if ($existingPoint) {
                DB::table($pointTableName)
                    ->where('gisid', $gisid)
                    ->update([
                        'type'        => 'point',
                        'coordinates' => json_encode($midpoint, JSON_UNESCAPED_UNICODE),
                        'updated_at'  => now(),
                    ]);
            } else {
                DB::table($pointTableName)->insert([
                    'gisid'       => $gisid,
                    'type'        => 'point',
                    'coordinates' => json_encode($midpoint, JSON_UNESCAPED_UNICODE),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } else {

            /*
            |--------------------------------------------------------------------------
            | INSERT NEW POLYGON
            |--------------------------------------------------------------------------
            */

            DB::table($tableName)->insert([
                'gisid'       => $gisid,
                'type'        => $layerType,
                'coordinates' => json_encode($coordinates, JSON_UNESCAPED_UNICODE),
                'sqfeet'      => (string) $sqfeet,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table($pointTableName)->insert([
                'gisid'       => $gisid,
                'type'        => 'point',
                'coordinates' => json_encode($midpoint, JSON_UNESCAPED_UNICODE),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if ($startedTransaction) {
            DB::commit();
        }

        return [
            'status'            => true,
            'gisid'             => $gisid,
            'message'           => 'Polygon updated successfully.',
            'point_data_exists' => $hasPointData,
            'sqfeet'            => $sqfeet,
            'midpoint'          => $midpoint,
            'polygons'          => DB::table($tableName)->get(),
            'points'            => DB::table($pointTableName)->get(),
        ];
    } catch (\Throwable $e) {

        if ($startedTransaction) {
            DB::rollBack();
        }

        Log::error('storeUpdatePolygon error', [
            'message' => $e->getMessage(),
            'gisid'   => $data['gisid'] ?? null,
            'feature' => $data['feature'] ?? null,
        ]);

        return [
            'status'  => false,
            'message' => $e->getMessage(),
        ];
    }}
    private function coordinateDepth($coords): int
{
    $depth = 0;
    while (is_array($coords) && isset($coords[0]) && is_array($coords[0])) {
        $coords = $coords[0];
        $depth++;
        if ($depth > 6) break; // safety
    }
    return $depth + 1; // +1 because the last value is numeric, not array
}

    public function deletePolygon(array $data): array
    {
        try {
            $wardId = $data['ward_id'];
            $gisid  = $data['gisid'];

            $polygonTable     = 'polygons_'     . $wardId;
            $pointTable       = 'points_'       . $wardId;
            $lineTable        = 'lines_'        . $wardId;
            $pointDataTable   = 'point_data_'   . $wardId;
            $polygonDataTable = 'polygon_data_' . $wardId;

            if (!Schema::hasTable($polygonTable)) {
                throw new \Exception("Polygon table not found: {$polygonTable}");
            }

            $exists = DB::table($polygonTable)->where('gisid', $gisid)->exists();
            if (!$exists) {
                throw new \Exception("Polygon not found with GIS ID: {$gisid}");
            }

            DB::beginTransaction();

            DB::table($polygonTable)->where('gisid', $gisid)->delete();

            if (Schema::hasTable($pointTable)) {
                DB::table($pointTable)->where('gisid', $gisid)->delete();
            }

            if (Schema::hasTable($pointDataTable)) {
                DB::table($pointDataTable)->where('point_gisid', $gisid)->delete();
            }

            if (Schema::hasTable($polygonDataTable)) {
                DB::table($polygonDataTable)->where('gisid', $gisid)->delete();
            }

            DB::commit();

            $allPolygons = DB::table($polygonTable)->get()->toArray();
            $allPoints   = Schema::hasTable($pointTable)
                ? DB::table($pointTable)->get()->toArray()
                : [];
            $allLines    = Schema::hasTable($lineTable)
                ? DB::table($lineTable)->get()->toArray()
                : [];
            $allPolygonDatas = Schema::hasTable($polygonDataTable)
                ? DB::table($polygonDataTable)->get()->toArray()
                : [];
            $allPointDatas = Schema::hasTable($pointDataTable)
                ? DB::table($pointDataTable)->get()->toArray()
                : [];

            return [
                'success' => true,
                'status'  => true,
                'message' => "Polygon {$gisid} deleted successfully.",
                'data'    => [
                    'polygons'     => $allPolygons,
                    'points'       => $allPoints,
                    'lines'        => $allLines,
                    'polygonDatas' => $allPolygonDatas,
                    'pointDatas'   => $allPointDatas,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete Polygon Error: ' . $e->getMessage());

            return [
                'success' => false,
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    public function deleteLine(array $data): array
    {
        try {
            $wardId = $data['ward_id'];
            $gisid  = $data['gisid'];

            $lineTable        = 'lines_'        . $wardId;
            $polygonTable     = 'polygons_'     . $wardId;
            $pointTable       = 'points_'       . $wardId;
            $pointDataTable   = 'point_data_'   . $wardId;
            $polygonDataTable = 'polygon_data_' . $wardId;

            if (!Schema::hasTable($lineTable)) {
                throw new \Exception("Line table not found: {$lineTable}");
            }

            $exists = DB::table($lineTable)->where('gisid', $gisid)->exists();
            if (!$exists) {
                throw new \Exception("Line not found with GIS ID: {$gisid}");
            }

            DB::beginTransaction();
            DB::table($lineTable)->where('gisid', $gisid)->delete();
            DB::commit();

            $allPolygons = Schema::hasTable($polygonTable) ? DB::table($polygonTable)->get()->toArray() : [];
            $allPoints   = Schema::hasTable($pointTable)   ? DB::table($pointTable)->get()->toArray()   : [];
            $allLines    = DB::table($lineTable)->get()->toArray();
            $allPolygonDatas = Schema::hasTable($polygonDataTable) ? DB::table($polygonDataTable)->get()->toArray() : [];
            $allPointDatas   = Schema::hasTable($pointDataTable)   ? DB::table($pointDataTable)->get()->toArray()   : [];

            return [
                'success' => true,
                'status'  => true,
                'message' => "Line {$gisid} deleted successfully.",
                'data'    => [
                    'polygons'     => $allPolygons,
                    'points'       => $allPoints,
                    'lines'        => $allLines,
                    'polygonDatas' => $allPolygonDatas,
                    'pointDatas'   => $allPointDatas,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete Line Error: ' . $e->getMessage());

            return [
                'success' => false,
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Flatten coordinates to a single representative ring
    //  (used ONLY for midpoint calculation, NOT for area or storage)
    // ─────────────────────────────────────────────────────────────

    private function flattenCoordinates(string $geometryType, array $coords): array
    {
        if ($geometryType === 'Polygon') {
            // coords = [ outerRing, hole1, hole2, ... ]
            return $coords[0] ?? [];
        }

        if ($geometryType === 'MultiPolygon') {
            // Take the largest polygon's outer ring (by vertex count)
            $largest = [];
            foreach ($coords as $polygon) {
                $outerRing = $polygon[0] ?? [];
                if (is_array($outerRing) && count($outerRing) > count($largest)) {
                    $largest = $outerRing;
                }
            }
            return $largest;
        }

        return [];
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Area calculation (FIXED — sums ALL polygons,
    //           subtracts holes)
    // ─────────────────────────────────────────────────────────────

    /**
     * Calculate total area (in square feet) for ANY polygon structure.
     *
     * Accepts:
     *   - Polygon:       [ ring1, ring2, ... ]
     *   - MultiPolygon:  [ polygon1, polygon2, ... ]
     *   - Flattened:     [ [x,y], [x,y], ... ]
     *
     * Sums all outer rings and subtracts all inner rings (holes).
     * Supports both EPSG:3857 (Web Mercator) and WGS84 (degrees).
     */
    private function calculatePolygonAreaInSquareFeet($coordinates): float
    {
        try {
            if (!is_array($coordinates) || empty($coordinates)) {
                Log::warning("Invalid coordinates array");
                return 0.0;
            }

            $polygons = $this->normalizeToPolygonList($coordinates);

            if (empty($polygons)) {
                Log::warning("Could not normalize coordinates to polygon list");
                return 0.0;
            }

            $totalAreaInSqMeters = 0.0;

            foreach ($polygons as $polygon) {
                if (!is_array($polygon) || empty($polygon)) {
                    continue;
                }

                // polygon = [ outerRing, hole1, hole2, ... ]
                $outerRing = $polygon[0] ?? null;

                if (!$outerRing || !is_array($outerRing) || count($outerRing) < 3) {
                    continue;
                }

                if (!$this->isValidRing($outerRing)) {
                    Log::warning("Invalid outer ring in polygon");
                    continue;
                }

                // Add outer ring area
                $totalAreaInSqMeters += $this->calculateRingAreaInMeters($outerRing);

                // Subtract holes
                for ($i = 1; $i < count($polygon); $i++) {
                    $hole = $polygon[$i];

                    if (!$hole || !is_array($hole) || count($hole) < 3) {
                        continue;
                    }

                    if (!$this->isValidRing($hole)) {
                        continue;
                    }

                    $totalAreaInSqMeters -= $this->calculateRingAreaInMeters($hole);
                }
            }

            $totalAreaInSqMeters = max(0, $totalAreaInSqMeters);

            if ($totalAreaInSqMeters <= 0) {
                Log::warning("Total area calculation returned 0 or negative");
                return 0.0;
            }

            $areaInSqFeet = $totalAreaInSqMeters * 10.7639;
            $result = round($areaInSqFeet, 0);

            Log::info("Calculated total area: {$result} sq ft");

            return (float) $result;
        } catch (\Exception $e) {
            Log::error("Area calculation failed: " . $e->getMessage());
            Log::error("Coordinates structure: " . json_encode(array_slice($coordinates, 0, 2)));
            return 0.0;
        }
    }

    /**
     * Normalize ANY polygon coordinate structure into a list of polygons.
     *
     * Accepts:
     *   - Polygon:       [ ring1, ring2, ... ]              (ring = [ [x,y], ... ])
     *   - MultiPolygon:  [ polygon1, polygon2, ... ]        (polygon = [ ring1, ... ])
     *   - Flattened:     [ [x,y], [x,y], ... ]              (single ring)
     *
     * Always returns:
     *   [ [ ring1, ring2, ... ], [ ring1, ... ], ... ]
     */
    private function normalizeToPolygonList(array $coordinates): array
    {
        if (empty($coordinates)) {
            return [];
        }

        $first = $coordinates[0] ?? null;

        if (!is_array($first)) {
            return [];
        }

        // Case 1: Flattened ring — [ [x,y], [x,y], ... ]
        if (isset($first[0]) && is_numeric($first[0])) {
            return [[$coordinates]];
        }

        $second = $first[0] ?? null;

        if (!is_array($second)) {
            return [];
        }

        // Case 2: Polygon — [ ring1, ring2, ... ]
        if (isset($second[0]) && is_numeric($second[0])) {
            return [$coordinates];
        }

        $third = $second[0] ?? null;

        if (!is_array($third)) {
            return [];
        }

        // Case 3: MultiPolygon — [ polygon1, polygon2, ... ]
        if (isset($third[0]) && is_numeric($third[0])) {
            return $coordinates;
        }

        Log::warning("Unknown coordinate structure in normalizeToPolygonList");
        return [];
    }

    /**
     * Validate a ring: must be an array of [x, y] numeric pairs with >= 3 points.
     */
    private function isValidRing(array $ring): bool
    {
        if (count($ring) < 3) {
            return false;
        }

        foreach ($ring as $point) {
            if (
                !is_array($point) ||
                !isset($point[0], $point[1]) ||
                !is_numeric($point[0]) ||
                !is_numeric($point[1])
            ) {
                Log::warning("Invalid point in ring: " . json_encode($point));
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate a single ring's area in square meters.
     * Auto-detects EPSG:3857 (Web Mercator) vs WGS84 (degrees).
     */
    private function calculateRingAreaInMeters(array $ring): float
    {
        $samplePoint = $ring[0] ?? null;

        if (!$samplePoint) {
            return 0.0;
        }

        // Web Mercator (large coordinates in meters)
        if (abs($samplePoint[0]) > 180 || abs($samplePoint[1]) > 90) {
            $area = $this->calculate3857AreaInMeters($ring);

            if ($area > 0 && $area < 1000000000) {
                return $area;
            }
        }

        // WGS84 (degrees)
        if (
            abs($samplePoint[0]) <= 180 &&
            abs($samplePoint[1]) <= 90
        ) {
            return $this->calculateSphericalAreaInMeters($ring);
        }

        return 0.0;
    }

    /**
     * Area for EPSG:3857 (Web Mercator) coordinates, with latitude correction.
     */
    private function calculate3857AreaInMeters($ring)
    {
        $count = count($ring);

        $rawArea = 0;
        for ($i = 0; $i < $count; $i++) {
            $p1 = $ring[$i];
            $p2 = $ring[($i + 1) % $count];
            $rawArea += ($p1[0] * $p2[1]) - ($p2[0] * $p1[1]);
        }
        $rawArea = abs($rawArea) / 2;

        $centerY = 0;
        foreach ($ring as $point) {
            $centerY += $point[1];
        }
        $centerY = $centerY / $count;

        $R = 6378137;
        $latitudeRad = atan(sinh($centerY / $R));

        $scaleFactor = 1 / (pow(cos($latitudeRad), 2));

        $correctedArea = $rawArea / $scaleFactor;

        return abs($correctedArea);
    }

    /**
     * Spherical area for WGS84 (degrees) coordinates.
     */
    private function calculateSphericalAreaInMeters($ring)
    {
        $earthRadius = 6378137;
        $area = 0;
        $count = count($ring);

        for ($i = 0; $i < $count; $i++) {
            $p1 = $ring[$i];
            $p2 = $ring[($i + 1) % $count];

            $lon1 = deg2rad($p1[0]);
            $lat1 = deg2rad($p1[1]);
            $lon2 = deg2rad($p2[0]);
            $lat2 = deg2rad($p2[1]);

            $area += ($lon2 - $lon1) * (2 + sin($lat1) + sin($lat2));
        }

        return abs($area * $earthRadius * $earthRadius / 2);
    }

    /**
     * Arithmetic centroid of a ring.
     */
    private function calculateMidpoint(array $ring): ?array
    {
        if (empty($ring)) {
            return null;
        }

        $lngSum = 0.0;
        $latSum = 0.0;
        $count  = 0;

        foreach ($ring as $point) {
            if (!isset($point[0], $point[1])) {
                continue;
            }
            $lngSum += (float) $point[0];
            $latSum += (float) $point[1];
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return [
            round($lngSum / $count, 8),
            round($latSum / $count, 8),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Merge two polygons into one MultiPolygon
    // ─────────────────────────────────────────────────────────────

    public function mergePolygons(array $data, $useTransaction = true): array
    {
        $startedTransaction = false;

        try {
            $wardId         = $data['ward_id'] ?? null;
            $primaryGisid   = $data['primary_gisid'] ?? null;
            $secondaryGisid = $data['secondary_gisid'] ?? null;

            if (empty($wardId)) {
                throw new \Exception('Ward ID is required.');
            }
            if (empty($primaryGisid)) {
                throw new \Exception('Primary GIS ID is required.');
            }
            if (empty($secondaryGisid)) {
                throw new \Exception('Secondary GIS ID is required.');
            }
            if ((string) $primaryGisid === (string) $secondaryGisid) {
                throw new \Exception('Primary and secondary GIS IDs cannot be the same.');
            }

            $polygonTable     = 'polygons_'     . $wardId;
            $pointTable       = 'points_'       . $wardId;
            $pointDataTable   = 'point_data_'   . $wardId;
            $polygonDataTable = 'polygon_data_' . $wardId;
            $lineTable        = 'lines_'        . $wardId;

            if (!Schema::hasTable($polygonTable)) {
                throw new \Exception("Polygon table not found: {$polygonTable}");
            }
            if (!Schema::hasTable($pointTable)) {
                throw new \Exception("Point table not found: {$pointTable}");
            }

            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            }

            $primaryPolygon = DB::table($polygonTable)
                ->where('gisid', $primaryGisid)
                ->first();

            if (!$primaryPolygon) {
                throw new \Exception("Primary polygon not found: {$primaryGisid}");
            }

            $secondaryPolygon = DB::table($polygonTable)
                ->where('gisid', $secondaryGisid)
                ->first();

            if (!$secondaryPolygon) {
                throw new \Exception("Secondary polygon not found: {$secondaryGisid}");
            }

            // Block merge if secondary has point data
            if (Schema::hasTable($pointDataTable)) {
                $hasPointData = DB::table($pointDataTable)
                    ->where('point_gisid', $secondaryGisid)
                    ->exists();

                if ($hasPointData) {
                    if ($startedTransaction) {
                        DB::rollBack();
                        $startedTransaction = false;
                    }

                    return [
                        'status'          => false,
                        'merge_allowed'   => false,
                        'message'         => "GISID {$secondaryGisid} contains point data. Merge cancelled.",
                        'primary_gisid'   => $primaryGisid,
                        'secondary_gisid' => $secondaryGisid,
                    ];
                }
            }

            $primaryCoordinates = json_decode($primaryPolygon->coordinates, true);
            $secondaryCoordinates = json_decode($secondaryPolygon->coordinates, true);

            if (!is_array($primaryCoordinates) || empty($primaryCoordinates)) {
                throw new \Exception("Invalid coordinates for primary GISID: {$primaryGisid}");
            }
            if (!is_array($secondaryCoordinates) || empty($secondaryCoordinates)) {
                throw new \Exception("Invalid coordinates for secondary GISID: {$secondaryGisid}");
            }

            $primaryMultiPolygon   = $this->toMultiPolygonCoordinates($primaryCoordinates);
            $secondaryMultiPolygon = $this->toMultiPolygonCoordinates($secondaryCoordinates);

            $mergedCoordinates = array_merge(
                $primaryMultiPolygon,
                $secondaryMultiPolygon
            );

            if (empty($mergedCoordinates)) {
                throw new \Exception('Merged MultiPolygon coordinates are empty.');
            }

            // ── Area calculation ──
            // Priority: user override → sum of stored sqfeet → recalculate
            $sqfeet = $data['sqfeet'] ?? null;

            if ($sqfeet !== null && $sqfeet !== '' && (float) $sqfeet > 0) {
                $sqfeet = (float) $sqfeet;
            } else {
                $primarySqfeet   = (float) ($primaryPolygon->sqfeet   ?? 0);
                $secondarySqfeet = (float) ($secondaryPolygon->sqfeet ?? 0);
                $simpleSum = $primarySqfeet + $secondarySqfeet;

                if ($simpleSum > 0) {
                    $sqfeet = $simpleSum;
                } else {
                    // Recalculate from merged geometry
                    $sqfeet = $this->calculatePolygonAreaInSquareFeet($mergedCoordinates);
                }
            }

            $sqfeet = (float) $sqfeet;

            // Update primary polygon
            DB::table($polygonTable)
                ->where('gisid', $primaryGisid)
                ->update([
                    'type'        => 'MultiPolygon',
                    'coordinates' => json_encode($mergedCoordinates, JSON_UNESCAPED_UNICODE),
                    'sqfeet'      => (string) $sqfeet,
                    'updated_at'  => now(),
                ]);

            // Delete secondary point + polygon
            DB::table($pointTable)
                ->where('gisid', $secondaryGisid)
                ->delete();

            DB::table($polygonTable)
                ->where('gisid', $secondaryGisid)
                ->delete();

            if ($startedTransaction) {
                DB::commit();
                $startedTransaction = false;
            }

            $mergedPolygon = DB::table($polygonTable)
                ->where('gisid', $primaryGisid)
                ->first();

            $mergedPoint = DB::table($pointTable)
                ->where('gisid', $primaryGisid)
                ->first();

            $allPolygons = DB::table($polygonTable)->get()->toArray();
            $allPoints   = DB::table($pointTable)->get()->toArray();

            $allLines = Schema::hasTable($lineTable)
                ? DB::table($lineTable)->get()->toArray()
                : [];

            $allPolygonDatas = Schema::hasTable($polygonDataTable)
                ? DB::table($polygonDataTable)->get()->toArray()
                : [];

            $allPointDatas = Schema::hasTable($pointDataTable)
                ? DB::table($pointDataTable)->get()->toArray()
                : [];

            return [
                'status'          => true,
                'merge_allowed'   => true,
                'message'         => "Polygon {$secondaryGisid} merged into {$primaryGisid} successfully.",
                'primary_gisid'   => $primaryGisid,
                'secondary_gisid' => $secondaryGisid,
                'type'            => 'MultiPolygon',
                'sqfeet'          => $sqfeet,
                'polygon'         => $mergedPolygon,
                'point'           => $mergedPoint,
                'polygons'        => $allPolygons,
                'points'          => $allPoints,
                'lines'           => $allLines,
                'polygonDatas'    => $allPolygonDatas,
                'pointDatas'      => $allPointDatas,
            ];
        } catch (\Throwable $e) {
            if ($startedTransaction) {
                try {
                    DB::rollBack();
                } catch (\Throwable $rollbackException) {
                    Log::error('Merge Polygon Rollback Error: ' . $rollbackException->getMessage());
                }
                $startedTransaction = false;
            }

            Log::error(
                'Merge Polygon Error: ' . $e->getMessage(),
                [
                    'ward_id'         => $data['ward_id']         ?? null,
                    'primary_gisid'   => $data['primary_gisid']   ?? null,
                    'secondary_gisid' => $data['secondary_gisid'] ?? null,
                ]
            );

            return [
                'status'          => false,
                'merge_allowed'   => false,
                'message'         => $e->getMessage(),
                'primary_gisid'   => $data['primary_gisid']   ?? null,
                'secondary_gisid' => $data['secondary_gisid'] ?? null,
            ];
        }
    }

    /**
     * Convert any polygon coordinate structure into MultiPolygon format:
     *   [ [ [ [x,y], ... ] ], [ [ [x,y], ... ] ], ... ]
     */
    private function toMultiPolygonCoordinates(array $coordinates): array
    {
        if (empty($coordinates)) {
            return [];
        }

        $first = $coordinates[0] ?? null;

        if (!is_array($first)) {
            return [];
        }

        // Case A: Flattened ring — [ [x,y], [x,y], ... ]
        if (isset($first[0]) && is_numeric($first[0])) {
            return [[$coordinates]];
        }

        $second = $first[0] ?? null;

        if (!is_array($second)) {
            return [];
        }

        // Case B: Polygon — [ ring1, ring2, ... ]
        if (isset($second[0]) && is_numeric($second[0])) {
            return [$coordinates];
        }

        $third = $second[0] ?? null;

        if (!is_array($third)) {
            return [];
        }

        // Case C: MultiPolygon — already correct
        if (isset($third[0]) && is_numeric($third[0])) {
            return $coordinates;
        }

        throw new \Exception('Unsupported polygon coordinate structure.');
    }
}
