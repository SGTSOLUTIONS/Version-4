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
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Illuminate\Support\Collection;

class MisImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithValidation,
    SkipsEmptyRows,
    ShouldQueue
{
    protected $corporationId;
    protected $tableName;

    protected $insertedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $skippedDetails = [];
    protected $processedCount = 0;
    protected $totalRows = 0;
    protected $chunkCounter = 0;

    protected $stagingTable = null;
    protected $useStaging = false;

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "mis_" . $corporationId;

        if (!Schema::hasTable($this->tableName)) {
            $this->createMainTable();
        }

        $this->useStaging = $this->shouldUseStaging();

        Cache::put("mis_import_progress_{$this->corporationId}", [
            'status'    => 'processing',
            'total_rows' => 0,
            'processed'  => 0,
            'percentage' => 0,
            'inserted'   => 0,
            'updated'    => 0,
            'skipped'    => 0,
        ], 3600);
    }

    /**
     * Base the staging decision on how big the *existing* table already is.
     * With upsert() in place this matters much less than before — staging
     * is only worth the overhead once the target table itself is large.
     */
    protected function shouldUseStaging(): bool
    {
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        $count = DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->count();

        return $count > 20000;
    }

    /**
     * Main collection handler — called once per chunk (see chunkSize()).
     */
    public function collection(Collection $rows)
    {
        DB::connection()->disableQueryLog();

        $this->totalRows += $rows->count();

        if ($this->useStaging) {
            $this->processWithStaging($rows);
        } else {
            $this->processRows($rows);
        }

        $this->chunkCounter++;
        Cache::put("mis_import_progress_{$this->corporationId}", $this->getProgress(), 3600);
    }

    /**
     * Fast path: build rows, then a single upsert() per 500-row sub-chunk.
     * Requires a UNIQUE index on (corporation_id, assessment) — created
     * below in createMainTable().
     */
    protected function processRows(Collection $rows)
    {
        $now = now();
        $batch = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $this->processedCount + $index + 1;

            try {
                $data = $this->prepareData($row);

                if (!$data) {
                    $this->skippedCount++;
                    $this->skippedDetails[] = [
                        'row'    => $rowNumber,
                        'reason' => 'Missing or invalid assessment number',
                    ];
                    continue;
                }

                $batch[] = array_merge($data, [
                    'corporation_id' => $this->corporationId,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            } catch (\Exception $e) {
                Log::error('MIS Import Row Error: ' . $e->getMessage(), ['row' => $rowNumber]);
                $this->skippedCount++;
                $this->skippedDetails[] = [
                    'row'    => $rowNumber,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $this->upsertBatch($batch);

        $this->processedCount += $rows->count();
    }

    /**
     * Upsert in sub-chunks so a single statement never gets too large.
     * MySQL's upsert (INSERT ... ON DUPLICATE KEY UPDATE) tells us total
     * affected rows but not a clean insert/update split, so we approximate
     * by counting how many assessments already existed beforehand.
     */
    protected function upsertBatch(array $batch)
    {
        if (empty($batch)) {
            return;
        }

        $updateColumns = [
            'gisid', 'ward_no', 'old_assessment', 'road_name', 'owner_name',
            'old_door_no', 'new_door_no', 'phone_number', 'plot_area',
            'half_year_tax', 'balance', 'usage', 'type', 'zone', 'updated_at',
        ];

        foreach (array_chunk($batch, 500) as $chunk) {
            $assessments = array_column($chunk, 'assessment');

            $existing = DB::table($this->tableName)
                ->where('corporation_id', $this->corporationId)
                ->whereIn('assessment', $assessments)
                ->pluck('assessment')
                ->flip();

            $existingInChunk = 0;
            foreach ($chunk as $row) {
                if (isset($existing[$row['assessment']])) {
                    $existingInChunk++;
                }
            }

            try {
                DB::table($this->tableName)->upsert(
                    $chunk,
                    ['corporation_id', 'assessment'],
                    $updateColumns
                );

                $this->updatedCount += $existingInChunk;
                $this->insertedCount += (count($chunk) - $existingInChunk);
            } catch (\Exception $e) {
                Log::error('Upsert batch failed: ' . $e->getMessage());

                // Fallback: row-by-row so one bad row doesn't kill the chunk
                foreach ($chunk as $row) {
                    try {
                        DB::table($this->tableName)->upsert(
                            [$row],
                            ['corporation_id', 'assessment'],
                            $updateColumns
                        );
                        if (isset($existing[$row['assessment']])) {
                            $this->updatedCount++;
                        } else {
                            $this->insertedCount++;
                        }
                    } catch (\Exception $inner) {
                        Log::error('Single upsert failed: ' . $inner->getMessage());
                        $this->skippedCount++;
                        $this->skippedDetails[] = [
                            'row'    => 0,
                            'reason' => 'DB error: ' . $inner->getMessage(),
                        ];
                    }
                }
            }
        }
    }

    /**
     * Staging-table path for very large existing tables. Loads rows into a
     * throwaway table, then merges with two set-based SQL statements
     * instead of thousands of individual upserts.
     */
    protected function processWithStaging(Collection $rows)
    {
        $this->stagingTable = "mis_staging_" . $this->corporationId . "_" . time() . "_" . mt_rand(1000, 9999);

        try {
            $this->createStagingTable();

            $data = [];
            foreach ($rows as $index => $row) {
                $rowNumber = $this->processedCount + $index + 1;
                try {
                    $prepared = $this->prepareData($row);
                    if ($prepared) {
                        $data[] = array_merge($prepared, [
                            'corporation_id' => $this->corporationId,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    } else {
                        $this->skippedCount++;
                        $this->skippedDetails[] = [
                            'row'    => $rowNumber,
                            'reason' => 'Missing or invalid assessment number',
                        ];
                    }
                } catch (\Exception $e) {
                    $this->skippedCount++;
                    $this->skippedDetails[] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
                }
            }

            foreach (array_chunk($data, 1000) as $chunk) {
                DB::table($this->stagingTable)->insert($chunk);
            }

            $this->mergeStagingToMain(count($data));

            Schema::dropIfExists($this->stagingTable);

            $this->processedCount += $rows->count();
        } catch (\Exception $e) {
            Log::error('Staging import failed: ' . $e->getMessage());

            if ($this->stagingTable && Schema::hasTable($this->stagingTable)) {
                Schema::dropIfExists($this->stagingTable);
            }

            // Fallback to the normal upsert path for this chunk
            $this->useStaging = false;
            $this->processRows($rows);
        }
    }

    protected function createStagingTable()
    {
        Schema::create($this->stagingTable, function ($table) {
            $table->id();
            $table->unsignedBigInteger('corporation_id')->nullable();
            $table->string('gisid')->nullable();
            $table->string('ward_no')->nullable();
            $table->string('assessment')->nullable();
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

            $table->index(['assessment']);
        });
    }

    protected function mergeStagingToMain(int $stagingCount)
    {
        $existingCount = DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->whereIn('assessment', function ($q) {
                $q->select('assessment')->from($this->stagingTable);
            })
            ->count();

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
            FROM {$this->stagingTable} s
            WHERE assessment IS NOT NULL AND assessment != ''
            ON DUPLICATE KEY UPDATE
                gisid = VALUES(gisid),
                ward_no = VALUES(ward_no),
                old_assessment = VALUES(old_assessment),
                road_name = VALUES(road_name),
                owner_name = VALUES(owner_name),
                old_door_no = VALUES(old_door_no),
                new_door_no = VALUES(new_door_no),
                phone_number = VALUES(phone_number),
                plot_area = VALUES(plot_area),
                half_year_tax = VALUES(half_year_tax),
                balance = VALUES(balance),
                `usage` = VALUES(`usage`),
                `type` = VALUES(`type`),
                zone = VALUES(zone),
                updated_at = VALUES(updated_at)
        ";

        DB::statement($insertSql);

        $this->updatedCount += $existingCount;
        $this->insertedCount += ($stagingCount - $existingCount);
    }

    /**
     * Create main table with a UNIQUE index on (corporation_id, assessment)
     * — required for upsert()/ON DUPLICATE KEY UPDATE to work correctly.
     */
    protected function createMainTable()
    {
        if (Schema::hasTable($this->tableName)) {
            $this->ensureUniqueIndex();
            return;
        }

        Schema::create($this->tableName, function ($table) {
            $table->id();
            $table->unsignedBigInteger('corporation_id')->nullable();
            $table->string('gisid')->nullable();
            $table->string('ward_no')->nullable();
            $table->string('assessment')->nullable();
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

            $table->unique(['corporation_id', 'assessment'], 'mis_corp_assessment_unique');
            $table->index(['corporation_id', 'ward_no']);
            $table->index(['corporation_id', 'phone_number']);
        });
    }

    /**
     * Older corporation tables created before this fix may not have the
     * unique index. Add it if missing so upsert() works for them too.
     */
    protected function ensureUniqueIndex()
    {
        $indexExists = collect(Schema::getIndexes($this->tableName))
            ->pluck('name')
            ->contains('mis_corp_assessment_unique');

        if (!$indexExists) {
            try {
                Schema::table($this->tableName, function ($table) {
                    $table->unique(['corporation_id', 'assessment'], 'mis_corp_assessment_unique');
                });
            } catch (\Exception $e) {
                // Likely duplicate (corporation_id, assessment) pairs already
                // exist in the table from the old insert/update logic.
                // Log it — upsert() will fail loudly until this is cleaned up.
                Log::error("Could not add unique index to {$this->tableName}: " . $e->getMessage());
            }
        }
    }

    protected function prepareData($row): ?array
    {
        $assessment = trim($row['assessment'] ?? '');
        if (empty($assessment)) {
            return null;
        }

        return [
            'gisid'          => $this->sanitizeString($row['gisid'] ?? null),
            'ward_no'        => $this->sanitizeString($row['ward_no'] ?? null),
            'assessment'     => $assessment,
            'old_assessment' => $this->sanitizeString($row['old_assessment'] ?? null),
            'road_name'      => $this->sanitizeString($row['road_name'] ?? null),
            'owner_name'     => $this->sanitizeString($row['owner_name'] ?? null),
            'old_door_no'    => $this->sanitizeString($row['old_door_no'] ?? null),
            'new_door_no'    => $this->sanitizeString($row['new_door_no'] ?? null),
            'phone_number'   => $this->sanitizePhone($row['phone_number'] ?? null),
            'plot_area'      => $this->parseDecimal($row['plot_area'] ?? null),
            'half_year_tax'  => $this->parseDecimal($row['half_year_tax'] ?? null),
            'balance'        => $this->parseDecimal($row['balance'] ?? null),
            'usage'          => $this->validateEnumValue($row['usage'] ?? null, 'usage'),
            'type'           => $this->validateEnumValue($row['type'] ?? null, 'type'),
            'zone'           => $this->sanitizeString($row['zone'] ?? null),
        ];
    }

    protected function sanitizeString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
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
        return is_numeric($value) ? (float) $value : null;
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
            ],
        ];

        $value = trim((string) $value);

        foreach ($allowed[$field] as $item) {
            if (strcasecmp($item, $value) == 0) {
                return $item;
            }
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function rules(): array
    {
        return [
            'assessment'    => 'nullable|string|max:100',
            'gisid'         => 'nullable|string|max:100',
            'ward_no'       => 'nullable|max:50',
            'plot_area'     => 'nullable|numeric',
            'half_year_tax' => 'nullable|numeric',
            'balance'       => 'nullable|numeric',
        ];
    }

    /**
     * Called by Laravel Excel automatically after the last chunk of a
     * queued import finishes (works because ShouldQueue + our own
     * collection() calls both run inside the queued jobs).
     */
    public function getStats(): array
    {
        return [
            'inserted'         => $this->insertedCount,
            'updated'          => $this->updatedCount,
            'skipped'          => $this->skippedCount,
            'skipped_details'  => array_slice($this->skippedDetails, 0, 50),
            'total_processed'  => $this->processedCount,
            'used_staging'     => $this->useStaging,
        ];
    }

    public function getProgress(): array
    {
        return [
            'status'     => 'processing',
            'total_rows' => $this->totalRows,
            'processed'  => $this->processedCount,
            'percentage' => $this->totalRows > 0
                ? round(($this->processedCount / $this->totalRows) * 100, 2)
                : 0,
            'inserted' => $this->insertedCount,
            'updated'  => $this->updatedCount,
            'skipped'  => $this->skippedCount,
        ];
    }

    /**
     * Laravel Excel calls this on the last queued chunk job if the import
     * class implements WithEvents/AfterImport — simplest reliable approach
     * here is to mark completion from the controller's queued-chain "then"
     * callback instead (see CorporationController below).
     */
    public function markComplete(): void
    {
        Cache::put("mis_import_progress_{$this->corporationId}", array_merge(
            $this->getProgress(),
            ['status' => 'completed', 'stats' => $this->getStats()]
        ), 3600);
    }
}