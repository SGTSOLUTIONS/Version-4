<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class MisImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, SkipsEmptyRows
{
    protected $corporationId;
    protected $tableName;
    protected $batchSize = 1000; // Increased for better performance
    protected $insertBatch = [];
    protected $updateBatch = [];
    protected $existingRecords = [];
    protected $insertedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $skippedDetails = [];
    protected $processedCount = 0;
    protected $totalRows = 0;
    protected $currentChunk = 0;
    protected $stagingTable = null;
    protected $useStaging = false;
    protected $chunkCounter = 0;

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "mis_" . $corporationId;
        
        // Determine if we should use staging table for large imports
        $this->useStaging = $this->shouldUseStaging();
        
        if (!$this->useStaging) {
            $this->loadExistingRecords();
        }
    }

    /**
     * Determine if staging table should be used
     */
    protected function shouldUseStaging(): bool
    {
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        $count = DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->count();

        // Use staging for tables with more than 100k records
        return $count > 100000;
    }

    /**
     * Load existing records with memory optimization
     */
    protected function loadExistingRecords()
    {
        if (!Schema::hasTable($this->tableName)) {
            return;
        }

        $count = DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->count();

        if ($count > 500000) {
            $this->loadExistingRecordsChunked();
        } else {
            $this->existingRecords = DB::table($this->tableName)
                ->where('corporation_id', $this->corporationId)
                ->pluck('id', 'assessment')
                ->toArray();
        }
    }

    /**
     * Load existing records in chunks
     */
    protected function loadExistingRecordsChunked()
    {
        $this->existingRecords = [];
        DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->select('id', 'assessment')
            ->chunk(10000, function ($chunk) {
                foreach ($chunk as $record) {
                    $this->existingRecords[$record->assessment] = $record->id;
                }
            });
    }

    /**
     * Main collection handler
     */
    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();
        $this->currentChunk++;

        // Disable query log for performance
        DB::connection()->disableQueryLog();

        // Use staging for large imports
        if ($this->useStaging) {
            $this->processWithStaging($rows);
            return;
        }

        // Process in smaller batches
        $rowChunks = $rows->chunk(500);
        
        foreach ($rowChunks as $chunk) {
            $this->processRows($chunk);
            
            // Update progress
            $this->chunkCounter++;
            if ($this->chunkCounter % 5 == 0) {
                Cache::put(
                    "mis_import_progress_{$this->corporationId}",
                    $this->getProgress(),
                    3600
                );
            }
        }

        // Flush remaining data
        $this->flushInserts();
        $this->flushUpdates();

        // Clean up
        $this->cleanup();
    }

    /**
     * Process with staging table for better performance
     */
    protected function processWithStaging(Collection $rows)
    {
        $this->stagingTable = "mis_staging_" . $this->corporationId . "_" . time();
        
        try {
            // Create staging table
            $this->createStagingTable();

            // Insert all rows into staging table in batches
            $stagingChunks = $rows->chunk(1000);
            foreach ($stagingChunks as $chunk) {
                $data = [];
                foreach ($chunk as $row) {
                    try {
                        $preparedData = $this->prepareData($row);
                        if ($preparedData && !empty($preparedData['assessment'])) {
                            $data[] = array_merge($preparedData, [
                                'corporation_id' => $this->corporationId,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->skippedCount++;
                    }
                }

                if (!empty($data)) {
                    DB::table($this->stagingTable)->insert($data);
                }
            }

            // Merge staging data into main table
            $this->mergeStagingToMain();

            // Drop staging table
            Schema::dropIfExists($this->stagingTable);

        } catch (\Exception $e) {
            Log::error('Staging import failed: ' . $e->getMessage());
            
            // Fallback to regular processing
            if (Schema::hasTable($this->stagingTable)) {
                Schema::dropIfExists($this->stagingTable);
            }
            
            $this->useStaging = false;
            $this->processRows($rows);
        }
    }

    /**
     * Create staging table
     */
    protected function createStagingTable()
    {
        if (!Schema::hasTable($this->stagingTable)) {
            Schema::create($this->stagingTable, function ($table) {
                $table->id();
                $table->unsignedBigInteger('corporation_id')->nullable();
                $table->string('gisid')->nullable();
                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable()->index();
                $table->string('old_assessment')->nullable();
                $table->string('road_name')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('old_door_no')->nullable();
                $table->string('new_door_no')->nullable();
                $table->string('phone_number')->nullable();
                $table->decimal('plot_area', 12, 2)->nullable();
                $table->decimal('half_year_tax', 12, 2)->nullable();
                $table->decimal('balance', 12, 2)->nullable();
                $table->string('usage')->nullable();
                $table->string('type')->nullable();
                $table->string('zone')->nullable();
                $table->timestamps();
                
                $table->index(['assessment', 'corporation_id']);
            });
        }
    }

    /**
     * Merge staging table to main table
     */
    protected function mergeStagingToMain()
    {
        if (!Schema::hasTable($this->tableName)) {
            // Create main table if it doesn't exist
            $this->createMainTable();
        }

        // Use a single query to merge data
        $insertSql = "
            INSERT INTO {$this->tableName} (
                corporation_id, gisid, ward_no, assessment, old_assessment,
                road_name, owner_name, old_door_no, new_door_no, phone_number,
                plot_area, half_year_tax, balance, `usage`, `type`, zone,
                created_at, updated_at
            )
            SELECT 
                corporation_id, gisid, ward_no, assessment, old_assessment,
                road_name, owner_name, old_door_no, new_door_no, phone_number,
                plot_area, half_year_tax, balance, `usage`, `type`, zone,
                created_at, updated_at
            FROM {$this->stagingTable}
            WHERE assessment IS NOT NULL AND assessment != ''
        ";

        // Try to insert, ignore duplicates
        try {
            DB::statement($insertSql);
            $this->insertedCount = DB::table($this->stagingTable)->count();
        } catch (\Exception $e) {
            Log::error('Merge staging failed: ' . $e->getMessage());
            
            // Fallback to insert ignore
            DB::statement("INSERT IGNORE INTO {$this->tableName} SELECT * FROM {$this->stagingTable}");
            $this->insertedCount = DB::table($this->stagingTable)->count();
        }

        // Update existing records
        $updateSql = "
            UPDATE {$this->tableName} t
            JOIN {$this->stagingTable} s ON t.assessment = s.assessment AND t.corporation_id = s.corporation_id
            SET 
                t.gisid = s.gisid,
                t.ward_no = s.ward_no,
                t.old_assessment = s.old_assessment,
                t.road_name = s.road_name,
                t.owner_name = s.owner_name,
                t.old_door_no = s.old_door_no,
                t.new_door_no = s.new_door_no,
                t.phone_number = s.phone_number,
                t.plot_area = s.plot_area,
                t.half_year_tax = s.half_year_tax,
                t.balance = s.balance,
                t.`usage` = s.`usage`,
                t.`type` = s.`type`,
                t.zone = s.zone,
                t.updated_at = NOW()
        ";

        try {
            DB::statement($updateSql);
            $this->updatedCount = DB::table($this->stagingTable)->count() - $this->insertedCount;
        } catch (\Exception $e) {
            Log::error('Update from staging failed: ' . $e->getMessage());
        }
    }

    /**
     * Create main table if it doesn't exist
     */
    protected function createMainTable()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function ($table) {
                $table->id();
                $table->unsignedBigInteger('corporation_id')->nullable();
                $table->string('gisid')->nullable();
                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable()->index();
                $table->string('old_assessment')->nullable();
                $table->string('road_name')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('old_door_no')->nullable();
                $table->string('new_door_no')->nullable();
                $table->string('phone_number')->nullable();
                $table->decimal('plot_area', 12, 2)->nullable();
                $table->decimal('half_year_tax', 12, 2)->nullable();
                $table->decimal('balance', 12, 2)->nullable();
                $table->enum('usage', [
                    'Residential', 'Commercial', 'Industrial', 'Institutional',
                    'Vacant', 'Agricultural', 'Mixed', 'Hospital', 'School',
                    'Temple', 'Others'
                ])->nullable();
                $table->enum('type', [
                    'Owner', 'Tenant', 'Mixed', 'Government', 'Lease',
                    'Trust', 'Partnership', 'Private Limited', 'Public Limited',
                    'Others'
                ])->nullable();
                $table->string('zone')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['corporation_id', 'assessment']);
                $table->index(['corporation_id', 'ward_no']);
                $table->index(['corporation_id', 'phone_number']);
            });
        }
    }

    /**
     * Process rows in batches
     */
    protected function processRows(Collection $rows)
    {
        $this->insertBatch = [];
        $this->updateBatch = [];

        // Get all assessments for batch lookup
        $assessments = $rows->pluck('assessment')
            ->map(fn($val) => trim($val))
            ->filter()
            ->toArray();

        // Batch fetch existing records
        $existingInChunk = [];
        if (!empty($assessments) && Schema::hasTable($this->tableName)) {
            $existingInChunk = DB::table($this->tableName)
                ->where('corporation_id', $this->corporationId)
                ->whereIn('assessment', $assessments)
                ->pluck('id', 'assessment')
                ->toArray();
        }

        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;

            try {
                $assessment = trim($row['assessment'] ?? '');

                if ($assessment === '') {
                    $this->skippedCount++;
                    continue;
                }

                $data = $this->prepareData($row);
                if (!$data) {
                    $this->skippedCount++;
                    continue;
                }

                // Check if record exists
                $existsId = $existingInChunk[$assessment] ?? 
                           ($this->existingRecords[$assessment] ?? null);

                if ($existsId) {
                    $data['id'] = $existsId;
                    $this->updateBatch[] = $data;
                    $this->updatedCount++;
                } else {
                    $data['corporation_id'] = $this->corporationId;
                    $data['created_at'] = now();
                    $data['updated_at'] = now();
                    $this->insertBatch[] = $data;
                    $this->insertedCount++;
                }

                $this->processedCount++;

                // Flush when batch size reached
                if (count($this->insertBatch) >= $this->batchSize) {
                    $this->flushInserts();
                }
                if (count($this->updateBatch) >= $this->batchSize) {
                    $this->flushUpdates();
                }

            } catch (\Exception $e) {
                Log::error('MIS Import Row Error: ' . $e->getMessage(), [
                    'row' => $rowIndex,
                    'data' => $row->toArray()
                ]);
                $this->skippedCount++;
                $this->skippedDetails[] = [
                    'row' => $rowIndex,
                    'reason' => $e->getMessage()
                ];
            }
        }

        // Update existing records cache
        $this->existingRecords = array_merge($this->existingRecords, $existingInChunk);
    }

    /**
     * Bulk insert with better performance
     */
    protected function flushInserts()
    {
        if (empty($this->insertBatch)) {
            return;
        }

        try {
            // Use insertOrIgnore for better performance
            DB::table($this->tableName)->insertOrIgnore($this->insertBatch);
        } catch (\Exception $e) {
            Log::error('Batch insert failed: ' . $e->getMessage());
            
            // Fallback to chunked inserts
            $chunks = array_chunk($this->insertBatch, 500);
            foreach ($chunks as $chunk) {
                try {
                    DB::table($this->tableName)->insert($chunk);
                } catch (\Exception $inner) {
                    Log::error('Chunk insert failed: ' . $inner->getMessage());
                    // Single insert fallback
                    foreach ($chunk as $data) {
                        try {
                            DB::table($this->tableName)->insert($data);
                        } catch (\Exception $single) {
                            Log::error('Single insert failed: ' . $single->getMessage());
                        }
                    }
                }
            }
        }

        $this->insertBatch = [];
    }

    /**
     * Bulk update with CASE statements for better performance
     */
    protected function flushUpdates()
    {
        if (empty($this->updateBatch)) {
            return;
        }

        try {
            // Use CASE WHEN for bulk updates
            $ids = array_column($this->updateBatch, 'id');
            $updateData = [];

            // Prepare update data
            foreach ($this->updateBatch as $data) {
                $id = $data['id'];
                unset($data['id']);
                unset($data['corporation_id']);
                unset($data['created_at']);
                $updateData[$id] = $data;
            }

            // Build CASE statements
            if (!empty($updateData)) {
                $this->bulkUpdateWithCase($updateData);
            }

        } catch (\Exception $e) {
            Log::error('Bulk update failed: ' . $e->getMessage());
            
            // Fallback to chunked updates
            $chunks = array_chunk($this->updateBatch, 100);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $data) {
                    try {
                        $id = $data['id'];
                        unset($data['id']);
                        unset($data['corporation_id']);
                        unset($data['created_at']);
                        
                        DB::table($this->tableName)
                            ->where('id', $id)
                            ->update($data);
                    } catch (\Exception $inner) {
                        Log::error('Single update failed: ' . $inner->getMessage());
                    }
                }
            }
        }

        $this->updateBatch = [];
    }

    /**
     * Perform bulk update using CASE statements
     */
    protected function bulkUpdateWithCase(array $updateData)
    {
        if (empty($updateData)) {
            return;
        }

        $ids = array_keys($updateData);
        $firstRow = reset($updateData);
        $columns = array_keys($firstRow);
        
        $cases = [];
        $bindings = [];

        foreach ($columns as $column) {
            $case = "{$column} = CASE id";
            foreach ($updateData as $id => $data) {
                $case .= " WHEN ? THEN ?";
                $bindings[] = $id;
                $bindings[] = $data[$column] ?? null;
            }
            $case .= " ELSE {$column} END";
            $cases[] = $case;
        }

        $bindings = array_merge($bindings, $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $cases) . " WHERE id IN ({$placeholders})";

        DB::statement($sql, $bindings);
    }

    /**
     * Prepare data with validation
     */
    protected function prepareData($row): ?array
    {
        $assessment = trim($row['assessment'] ?? '');
        if (empty($assessment)) {
            return null;
        }

        return [
            'gisid' => $this->sanitizeString($row['gisid'] ?? null),
            'ward_no' => $this->sanitizeString($row['ward_no'] ?? null),
            'assessment' => $assessment,
            'old_assessment' => $this->sanitizeString($row['old_assessment'] ?? null),
            'road_name' => $this->sanitizeString($row['road_name'] ?? null),
            'owner_name' => $this->sanitizeString($row['owner_name'] ?? null),
            'old_door_no' => $this->sanitizeString($row['old_door_no'] ?? null),
            'new_door_no' => $this->sanitizeString($row['new_door_no'] ?? null),
            'phone_number' => $this->sanitizePhone($row['phone_number'] ?? null),
            'plot_area' => $this->parseDecimal($row['plot_area'] ?? null),
            'half_year_tax' => $this->parseDecimal($row['half_year_tax'] ?? null),
            'balance' => $this->parseDecimal($row['balance'] ?? null),
            'usage' => $this->validateEnumValue($row['usage'] ?? null, 'usage'),
            'type' => $this->validateEnumValue($row['type'] ?? null, 'type'),
            'zone' => $this->sanitizeString($row['zone'] ?? null),
        ];
    }

    /**
     * Helper methods
     */
    protected function sanitizeString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string)$value);
    }

    protected function sanitizePhone($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9+\-()\s]/', '', $value);
        return $cleaned ?: null;
    }

    protected function parseDecimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_string($value)) {
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }
        
        return is_numeric($value) ? (float)$value : null;
    }

    protected function validateEnumValue($value, $field): ?string
    {
        if (!$value) {
            return null;
        }
        
        $allowed = [
            'usage' => [
                'Residential', 'Commercial', 'Industrial', 'Institutional',
                'Vacant', 'Agricultural', 'Mixed', 'Hospital', 'School',
                'Temple', 'Others'
            ],
            'type' => [
                'Owner', 'Tenant', 'Mixed', 'Government', 'Lease',
                'Trust', 'Partnership', 'Private Limited', 'Public Limited',
                'Others'
            ]
        ];
        
        $value = trim((string)$value);
        
        foreach ($allowed[$field] as $item) {
            if (strcasecmp($item, $value) == 0) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * Clean up resources
     */
    protected function cleanup()
    {
        $this->insertBatch = [];
        $this->updateBatch = [];
        
        // Clear progress from cache after completion
        Cache::forget("mis_import_progress_{$this->corporationId}");
    }

    /**
     * Excel import configuration
     */
    public function chunkSize(): int
    {
        return 5000; // Process 5000 rows per chunk
    }

    public function rules(): array
    {
        return [
            'assessment' => 'nullable|string|max:100',
            'gisid' => 'nullable|string|max:100',
            'ward_no' => 'nullable|max:50',
            'plot_area' => 'nullable|numeric',
            'half_year_tax' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
        ];
    }

    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return [
            'inserted' => $this->insertedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'skipped_details' => array_slice($this->skippedDetails, 0, 50),
            'total_processed' => $this->processedCount,
            'used_staging' => $this->useStaging,
        ];
    }

    /**
     * Get import progress
     */
    public function getProgress(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'processed' => $this->processedCount,
            'percentage' => $this->totalRows > 0 
                ? round(($this->processedCount / $this->totalRows) * 100, 2) 
                : 0,
            'inserted' => $this->insertedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
        ];
    }
}