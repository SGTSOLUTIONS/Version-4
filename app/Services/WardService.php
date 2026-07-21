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
    //  Returns an array of the actual table name strings.
    // ─────────────────────────────────────────────────────────────

    public function createWardTables($wardId): array
    {
        // Don't use transactions here - table creation should be separate
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
        $useTransaction = true  // Add parameter to control transaction
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

            // Only start transaction if not already in one
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

                // Support common GIS_ID property key variations
                $gisid = $feature['properties']['GIS_ID']
                    ?? $feature['properties']['gisid']
                    ?? $feature['properties']['GisId']
                    ?? $feature['properties']['GISID'] ?? $this->checkGISID($polygonTable)
                    ?? uniqid('GIS_');

                // Only flatten / insert polygons; skip points / lines
                if (!in_array($geometryType, ['Polygon', 'MultiPolygon'])) {
                    continue;
                }

                $flattened = $this->flattenCoordinates($geometryType, $coords);

                if (empty($flattened)) {
                    continue;
                }

                $sqfeet = $this->calculatePolygonAreaInSquareFeet($flattened, $geometryType);

                $polygonData = [
                    'type'        => 'Polygon',
                    'coordinates' => json_encode($flattened, JSON_UNESCAPED_UNICODE),
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

                // Derive a centroid point and upsert into the points table
                $midpoint = $this->calculateMidpoint($flattened);

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

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'Polygon and Point data updated successfully.',
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
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
        $gisid = $prefix . $newGisNumber;
        return $gisid;
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

            // Only start transaction if not already in one
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

                $lineData = [
                    'type'        => 'LineString',
                    'coordinates' => json_encode($coords, JSON_UNESCAPED_UNICODE),
                    'road_name'   => $roadName,
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

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'Line data updated successfully.',
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
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

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Get all table name strings for a ward
    // ─────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC: Check if all ward tables exist
    // ─────────────────────────────────────────────────────────────

    public function checkWardTablesExist($wardId): array
    {
        $missingTables = [];

        foreach ($this->getWardTables($wardId) as $key => $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $key;
            }
        }

        return [
            'all_exist'     => empty($missingTables),
            'missing_tables' => $missingTables,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Create individual tables (each returns table name)
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
    //  PUBLIC: Store single polygon (WITHOUT transaction)
    // ─────────────────────────────────────────────────────────────

    public function storeSinglePolygon($data, $useTransaction = true)
    {
        try {
            // Only start transaction if requested and not already in one
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            $tableName = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_' . $data['ward_id'];

            $feature = json_decode($data['feature'], true);
            $sqfeet = $this->calculatePolygonAreaInSquareFeet($feature, $data['layer_type']);

            $midpoint = $this->calculateMidpoint($feature[0]);

            // Generate GIS ID
            $gisid = $this->checkGISID($tableName) ?? uniqid('GIS_');

            // Store Polygon
            DB::table($tableName)->insert([
                'gisid'       => $gisid,
                'type'  => $data['layer_type'],
                'coordinates' => json_encode($feature[0]),
                'sqfeet'      => $sqfeet,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Store Mid Point (only if midpoint exists)
            if ($midpoint) {
                DB::table($pointTableName)->insert([
                    'gisid'       => $gisid,
                    'type'  => 'point',
                    'coordinates' => json_encode($midpoint),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            $points = DB::table($pointTableName)->get();
            $polygons =  DB::table($tableName)->get();

            return [
                'status'  => true,
                'gisid'   => $gisid,
                'message' => 'Polygon stored successfully',
                'polygons' => $polygons,
                'points' => $points
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
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

    public function storeSingleLine($tableName, $file, $useTransaction = true)
    {
        try {
            // Only start transaction if requested and not already in one
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            // ─── READ AND PARSE GEOJSON FILE ───
            if (is_string($file)) {
                // If file is a path string
                $geoJsonContent = file_get_contents($file);
            } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                // If file is uploaded file object (Illuminate\Http\UploadedFile)
                $geoJsonContent = file_get_contents($file->getRealPath());
            } else {
                throw new \Exception('Invalid file parameter');
            }

            $geoJson = json_decode($geoJsonContent, true);

            if (!$geoJson || !isset($geoJson['type'])) {
                throw new \Exception('Invalid GeoJSON file format');
            }

            // Check if table exists, if not create it
            if (!Schema::hasTable($tableName)) {
                // Extract ward_id from table name (lines_1 -> 1)
                $wardId = (int) str_replace('lines_', '', $tableName);
                $this->createLineTable($wardId);
            }

            // ─── PROCESS EACH FEATURE ───
            $processedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Get features from GeoJSON
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
                    // Validate feature
                    if (!isset($feature['geometry']) || !isset($feature['geometry']['type'])) {
                        $skippedCount++;
                        continue;
                    }

                    $geometryType = $feature['geometry']['type'];

                    // Only process LineString and MultiLineString
                    if ($geometryType !== 'LineString' && $geometryType !== 'MultiLineString') {
                        $skippedCount++;
                        continue;
                    }

                    // Get properties
                    $properties = $feature['properties'] ?? [];

                    // Get GIS ID from properties or generate new
                    $gisid = $properties['gisid'] ??
                        $properties['GIS_ID'] ??
                        $properties['id'] ??
                        $properties['ID'] ??
                        null;

                    $isUpdate = false;

                    // If GIS ID exists, check if it exists in database
                    if ($gisid) {
                        $existing = DB::table($tableName)->where('gisid', $gisid)->first();
                        if ($existing) {
                            $isUpdate = true;
                        }
                    }

                    // Prepare coordinates
                    $coordinates = $feature['geometry']['coordinates'];

                    // Prepare road_name - check various possible field names
                    $roadName = $properties['road_name'] ??
                        $properties['name'] ??
                        $properties['ROAD_NAME'] ??
                        $properties['road'] ??
                        $properties['RoadName'] ??
                        $properties['ROAD'] ??
                        null;

                    if ($isUpdate) {
                        // ─── UPDATE EXISTING LINE ───
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
                        // ─── INSERT NEW LINE ───
                        // Generate new GIS ID if not provided
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

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            // Get all lines for return
            $lines = DB::table($tableName)->get();

            return [
                'status'         => true,
                'message'        => "Processed $processedCount features successfully",
                'processed'      => $processedCount,
                'inserted'       => $insertedCount,
                'updated'        => $updatedCount,
                'skipped'        => $skippedCount,
                'errors'         => $errors,
                'lines'          => $lines,
                'table'          => $tableName
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
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

    /**
     * Generate unique GIS ID for line
     */
    private function generateLineGISID($tableName): string
    {
        $prefix = 'LINE_';
        $timestamp = time();
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $gisid = $prefix . $timestamp . '_' . $random;

        // Check if exists
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
        try {
            // Only start transaction if requested and not already in one
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            $tableName      = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_'   . $data['ward_id'];

            $features = is_string($data['feature'])
                ? json_decode($data['feature'], true)
                : $data['feature'];

            if (!$features || count($features) < 2) {
                throw new \Exception('Split polygon must return at least 2 polygons');
            }

            $originalGisid = $data['gisid'];

            $originalPolygon = DB::table($tableName)
                ->where('gisid', $originalGisid)
                ->first();

            if (!$originalPolygon) {
                throw new \Exception("Polygon with GIS ID '{$originalGisid}' not found");
            }

            foreach ($features as $index => $coords) {
                $sqfeet   = $this->calculatePolygonAreaInSquareFeet([$coords]);
                $midpoint = $this->calculateMidpoint($coords);

                if ($index === 0) {
                    // Update original polygon with first split piece
                    DB::table($tableName)
                        ->where('gisid', $originalGisid)
                        ->update([
                            'coordinates' => json_encode($coords),
                            'sqfeet'      => $sqfeet,
                            'updated_at'  => now(),
                        ]);

                    if ($midpoint) {
                        $pointExists = DB::table($pointTableName)
                            ->where('gisid', $originalGisid)
                            ->exists();

                        if ($pointExists) {
                            DB::table($pointTableName)
                                ->where('gisid', $originalGisid)
                                ->update([
                                    'coordinates' => json_encode($midpoint),
                                    'updated_at'  => now(),
                                ]);
                        } else {
                            DB::table($pointTableName)->insert([
                                'gisid'       => $originalGisid,
                                'type'        => 'point',
                                'coordinates' => json_encode($midpoint),
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);
                        }
                    }
                } else {
                    $newGisid = $this->checkGISID($tableName);

                    DB::table($tableName)->insert([
                        'gisid'       => $newGisid,
                        'type'        => $originalPolygon->type,
                        'coordinates' => json_encode($coords),
                        'sqfeet'      => $sqfeet,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    if ($midpoint) {
                        DB::table($pointTableName)->insert([
                            'gisid'       => $newGisid,
                            'type'        => 'point',
                            'coordinates' => json_encode($midpoint),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            return [
                'status'  => true,
                'message' => 'Polygon split and stored successfully',
                'polygons' => DB::table($tableName)->get(),
                'points'   => DB::table($pointTableName)->get(),
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('storeSplitPolygon error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ];
        }
    }

    public function storeUpdatePolygon($data, $useTransaction = true)
    {
        try {
            // Only start transaction if requested and not already in one
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            $tableName = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_' . $data['ward_id'];

            $feature = json_decode($data['feature'], true);
            $sqfeet = $this->calculatePolygonAreaInSquareFeet($feature);

            $midpoint = $this->calculateMidpoint($feature[0]);

            // Generate GIS ID
            $gisid = $data['gisid'];

            // Check if polygon already exists
            $existingPolygon = DB::table($tableName)->where('gisid', $gisid)->first();

            if ($existingPolygon) {
                // Update existing polygon
                DB::table($tableName)
                    ->where('gisid', $gisid)
                    ->update([
                        'type'  => $data['layer_type'],
                        'coordinates' => json_encode($feature[0]),
                        'sqfeet'      => $sqfeet,
                        'updated_at'  => now(),
                    ]);

                // Update or insert midpoint
                $existingPoint = DB::table($pointTableName)->where('gisid', $gisid)->first();
                if ($midpoint) {
                    if ($existingPoint) {
                        DB::table($pointTableName)
                            ->where('gisid', $gisid)
                            ->update([
                                'type'  => 'point',
                                'coordinates' => json_encode($midpoint),
                                'updated_at'  => now(),
                            ]);
                    } else {
                        DB::table($pointTableName)->insert([
                            'gisid'       => $gisid,
                            'type'  => 'point',
                            'coordinates' => json_encode($midpoint),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            } else {
                // Insert new polygon
                DB::table($tableName)->insert([
                    'gisid'       => $gisid,
                    'type'  => $data['layer_type'],
                    'coordinates' => json_encode($feature[0]),
                    'sqfeet'      => $sqfeet,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Insert midpoint
                if ($midpoint) {
                    DB::table($pointTableName)->insert([
                        'gisid'       => $gisid,
                        'type'  => 'point',
                        'coordinates' => json_encode($midpoint),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            // Fetch updated data
            $points = DB::table($pointTableName)->get();
            $polygons = DB::table($tableName)->get();

            return [
                'status'  => true,
                'gisid'   => $gisid,
                'message' => 'Polygon stored successfully',
                'polygons' => $polygons,
                'points' => $points
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('storeUpdatePolygon error: ' . $e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ];
        }
    }

    public function deletePolygon($data, $useTransaction = true)
    {
        try {
            // Only start transaction if requested and not already in one
            if ($useTransaction && DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            } else {
                $startedTransaction = false;
            }

            $tableName = 'polygons_' . $data['ward_id'];
            $pointTableName = 'points_' . $data['ward_id'];

            $gisid = $data['gisid'];

            // Check polygon exists
            $polygonExists = DB::table($tableName)
                ->where('gisid', $gisid)
                ->exists();

            if (!$polygonExists) {
                return [
                    'status' => false,
                    'message' => 'Polygon not found'
                ];
            }

            // Delete polygon
            DB::table($tableName)
                ->where('gisid', $gisid)
                ->delete();

            // Delete corresponding point
            DB::table($pointTableName)
                ->where('gisid', $gisid)
                ->delete();

            // Only commit if we started the transaction
            if ($startedTransaction) {
                DB::commit();
            }

            // Get remaining data
            $polygons = DB::table($tableName)->get();
            $points = DB::table($pointTableName)->get();

            return [
                'status' => true,
                'gisid' => $gisid,
                'message' => 'Polygon deleted successfully',
                'polygons' => $polygons,
                'points' => $points
            ];
        } catch (\Exception $e) {
            // Only rollback if we started the transaction
            if (isset($startedTransaction) && $startedTransaction) {
                DB::rollBack();
            }
            Log::error('deletePolygon error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Geometry helpers (unchanged)
    // ─────────────────────────────────────────────────────────────

    /**
     * Normalise Polygon / MultiPolygon coordinates into a single
     * flat outer ring (array of [lng, lat] pairs).
     *
     * Polygon coords:      [ [ [lng,lat], ... ] ]
     * MultiPolygon coords: [ [ [ [lng,lat], ... ] ] ]
     */
    private function flattenCoordinates(string $geometryType, array $coords): array
    {
        if ($geometryType === 'Polygon') {
            // coords[0] is the outer ring
            return $coords[0] ?? [];
        }

        if ($geometryType === 'MultiPolygon') {
            // Take the largest polygon ring (by vertex count) as the representative shape
            $largest = [];
            foreach ($coords as $polygon) {
                $outerRing = $polygon[0] ?? [];
                if (count($outerRing) > count($largest)) {
                    $largest = $outerRing;
                }
            }
            return $largest;
        }

        return [];
    }

    /**
     * Shoelace formula → area in square feet.
     * Coordinates are assumed to be geographic [lng, lat].
     * We convert degrees → metres using an equirectangular approximation
     * then metres² → ft².
     *
     * @param  array  $ring          Outer ring: [ [lng, lat], ... ]
     * @param  string $geometryType  'Polygon' | 'MultiPolygon'
     */
    private function calculatePolygonAreaInSquareFeet($coordinates)
    {
        try {
            if (!is_array($coordinates) || empty($coordinates)) {
                Log::warning("Invalid coordinates array");
                return 0;
            }

            // Extract the outer ring (first ring of first polygon)
            $ring = null;

            // Check structure and extract appropriate ring
            if (isset($coordinates[0]) && is_array($coordinates[0])) {
                // Check if it's a Polygon (array of rings)
                if (isset($coordinates[0][0]) && is_array($coordinates[0][0])) {
                    // Polygon structure: [[[x,y],[x,y]], [[x,y],[x,y]]]
                    $ring = $coordinates[0]; // First ring (outer boundary)
                }
                // Check if it's a MultiPolygon
                elseif (isset($coordinates[0][0][0]) && is_array($coordinates[0][0][0])) {
                    // MultiPolygon structure: [[[[x,y],[x,y]]], [[[x,y],[x,y]]]]
                    $ring = $coordinates[0][0]; // First polygon's first ring
                }
                // Check if already flattened points
                elseif (isset($coordinates[0][0]) && is_numeric($coordinates[0][0])) {
                    // Flattened structure: [[x,y],[x,y]]
                    $ring = $coordinates;
                } else {
                    Log::warning("Unsupported coordinate structure");
                    return 0;
                }
            } else {
                Log::warning("Invalid coordinate structure - missing first level array");
                return 0;
            }

            if (!$ring || !is_array($ring) || count($ring) < 3) {
                Log::warning("Invalid polygon ring - need at least 3 points");
                return 0;
            }

            // Validate ring points
            foreach ($ring as $point) {
                if (!isset($point[0], $point[1]) || !is_numeric($point[0]) || !is_numeric($point[1])) {
                    Log::warning("Invalid point in ring: " . json_encode($point));
                    return 0;
                }
            }

            $areaInSqMeters = $this->calculate3857AreaInMeters($ring);

            if ($areaInSqMeters > 0 && $areaInSqMeters < 1000000) {
                $areaInSqFeet = $areaInSqMeters * 10.7639;
                $result = round($areaInSqFeet, 0);
                Log::info("Calculated area: {$result} sq ft");
                return $result;
            }

            // Try spherical calculation if coordinates appear to be in degrees
            $samplePoint = $ring[0];
            if (
                isset($samplePoint[0], $samplePoint[1]) &&
                abs($samplePoint[0]) <= 180 &&
                abs($samplePoint[1]) <= 90
            ) {

                $areaInSqMeters = $this->calculateSphericalAreaInMeters($ring);
                $areaInSqFeet = $areaInSqMeters * 10.7639;
                $result = round($areaInSqFeet, 0);
                Log::info("Calculated spherical area: {$result} sq ft");
                return $result;
            }

            Log::warning("Area calculation returned unreasonable value");
            return 0;
        } catch (\Exception $e) {
            Log::error("Area calculation failed: " . $e->getMessage());
            Log::error("Coordinates structure: " . json_encode(array_slice($coordinates, 0, 2)));
            return 0;
        }
    }

    /**
     * Calculate area for EPSG:3857 (Web Mercator) coordinates
     * Web Mercator has significant distortion, so we need to correct for latitude
     *
     * @param array $ring - Polygon ring in EPSG:3857 coordinates (meters)
     * @return float - Area in square meters (corrected)
     */
    private function calculate3857AreaInMeters($ring)
    {
        $count = count($ring);

        // First, calculate the raw planar area (this will be distorted)
        $rawArea = 0;
        for ($i = 0; $i < $count; $i++) {
            $p1 = $ring[$i];
            $p2 = $ring[($i + 1) % $count];
            $rawArea += ($p1[0] * $p2[1]) - ($p2[0] * $p1[1]);
        }
        $rawArea = abs($rawArea) / 2;

        // Calculate the centroid latitude to get the scale factor
        $centerY = 0;
        foreach ($ring as $point) {
            $centerY += $point[1];
        }
        $centerY = $centerY / $count;

        // Convert Web Mercator Y coordinate to latitude in radians
        // Formula: lat = atan(sinh(y / R)) where R = 6378137
        $R = 6378137; // Earth radius in meters
        $latitudeRad = atan(sinh($centerY / $R));

        // The scale factor for area in Web Mercator is 1 / cos(latitude)^2
        // Because distortion is proportional to sec(latitude)^2
        $scaleFactor = 1 / (pow(cos($latitudeRad), 2));

        // Apply correction
        $correctedArea = $rawArea / $scaleFactor;

        // Ensure we return a positive number
        return abs($correctedArea);
    }

    /**
     * Calculate area using spherical formula (for WGS84 degrees)
     * Returns area in square meters
     */
    private function calculateSphericalAreaInMeters($ring)
    {
        $earthRadius = 6378137; // Earth radius in meters
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
     * Return the arithmetic centroid [lng, lat] of a ring.
     *
     * @param  array $ring  [ [lng, lat], ... ]
     * @return array|null
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
}
