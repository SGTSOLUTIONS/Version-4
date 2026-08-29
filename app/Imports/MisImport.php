<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class MisImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $corporationId;
    protected $tableName;

    protected $skippedRows = [];

    protected $insertedCount = 0;
    protected $updatedCount = 0;

    // Keep batch small to avoid MySQL 1390 error
    protected $batchSize = 500;

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = 'mis_' . $corporationId;
    }

    /**
     * Process Excel rows
     */
    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception(
                "MIS table {$this->tableName} not found."
            );
        }

        $batch = [];

        foreach ($rows as $index => $row) {

            try {

                $assessment = trim(
                    (string) ($row['assessment'] ?? '')
                );

                $wardNo = trim(
                    (string) ($row['ward_no'] ?? '')
                );

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                if ($assessment === '') {

                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'Assessment Empty'
                    ];

                    continue;
                }

                if ($wardNo === '') {

                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'Ward Empty'
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Prepare Data
                |--------------------------------------------------------------------------
                */

                $batch[] = $this->prepareData($row);

                /*
                |--------------------------------------------------------------------------
                | Process Every 500 Rows
                |--------------------------------------------------------------------------
                */

                if (count($batch) >= $this->batchSize) {

                    $this->upsertBatch($batch);

                    $batch = [];
                }

            } catch (\Throwable $e) {

                Log::error(
                    'MIS Import Error: ' . $e->getMessage()
                );

                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage()
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Process Remaining Rows
        |--------------------------------------------------------------------------
        */

        if (!empty($batch)) {

            $this->upsertBatch($batch);

            $batch = [];
        }
    }

    /**
     * Prepare data
     */
    protected function prepareData($row)
    {
        return [

            'corporation_id' => $this->corporationId,

            'gisid' => $row['gisid'] ?? null,

            'ward_no' => trim(
                (string) ($row['ward_no'] ?? '')
            ),

            'assessment' => trim(
                (string) ($row['assessment'] ?? '')
            ),

            'old_assessment' =>
                $row['old_assessment'] ?? null,

            'road_name' =>
                $row['road_name'] ?? null,

            'owner_name' =>
                $row['owner_name'] ?? null,

            'old_door_no' =>
                $row['old_door_no'] ?? null,

            'new_door_no' =>
                $row['new_door_no'] ?? null,

            'phone_number' =>
                $row['phone_number'] ?? null,

            'plot_area' =>
                $this->parseDecimal(
                    $row['plot_area'] ?? null
                ),

            'half_year_tax' =>
                $this->parseDecimal(
                    $row['half_year_tax'] ?? null
                ),

            'balance' =>
                $this->parseDecimal(
                    $row['balance'] ?? null
                ),

            'usage' =>
                $this->validateEnumValue(
                    $row['usage'] ?? null,
                    'usage'
                ),

            'type' =>
                $this->validateEnumValue(
                    $row['type'] ?? null,
                    'type'
                ),

            'zone' =>
                $row['zone'] ?? null,

            'created_at' => now(),

            'updated_at' => now(),
        ];
    }

    /**
     * Insert / Update batch
     */
    protected function upsertBatch(array $batch)
    {
        if (empty($batch)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get all assessments in this batch
        |--------------------------------------------------------------------------
        */

        $assessments = [];

        foreach ($batch as $row) {

            $key = $row['ward_no'] . '|' . $row['assessment'];

            $assessments[$key] = [
                'ward_no' => $row['ward_no'],
                'assessment' => $row['assessment'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Check existing records in ONE query
        |--------------------------------------------------------------------------
        */

        $existing = DB::table($this->tableName)
            ->where('corporation_id', $this->corporationId)
            ->where(function ($query) use ($assessments) {

                foreach ($assessments as $item) {

                    $query->orWhere(function ($q) use ($item) {

                        $q->where(
                            'ward_no',
                            $item['ward_no']
                        );

                        $q->where(
                            'assessment',
                            $item['assessment']
                        );

                    });
                }

            })
            ->get([
                'ward_no',
                'assessment'
            ]);

        /*
        |--------------------------------------------------------------------------
        | Create existing lookup
        |--------------------------------------------------------------------------
        */

        $existingLookup = [];

        foreach ($existing as $record) {

            $key =
                $record->ward_no .
                '|' .
                $record->assessment;

            $existingLookup[$key] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Count INSERT / UPDATE
        |--------------------------------------------------------------------------
        */

        foreach ($batch as $row) {

            $key =
                $row['ward_no'] .
                '|' .
                $row['assessment'];

            if (isset($existingLookup[$key])) {

                $this->updatedCount++;

            } else {

                $this->insertedCount++;

                // Important:
                // If the same key appears twice in this
                // Excel batch, second one should be treated
                // as update.
                $existingLookup[$key] = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Bulk UPSERT
        |--------------------------------------------------------------------------
        */

        DB::table($this->tableName)->upsert(

            $batch,

            [
                'corporation_id',
                'ward_no',
                'assessment'
            ],

            [
                'gisid',
                'old_assessment',
                'road_name',
                'owner_name',
                'old_door_no',
                'new_door_no',
                'phone_number',
                'plot_area',
                'half_year_tax',
                'balance',
                'usage',
                'type',
                'zone',
                'updated_at'
            ]
        );
    }

    /**
     * Excel chunk size
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Parse decimal
     */
    private function parseDecimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = preg_replace(
            '/[^0-9.\-]/',
            '',
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Validate enum
     */
    private function validateEnumValue($value, $field)
    {
        if (!$value) {
            return null;
        }

        $allowed = [

            'usage' => [
                'Residential',
                'Commercial',
                'Industrial',
                'Institutional',
                'Vacant',
                'Agricultural',
                'Mixed',
                'Hospital',
                'School',
                'Temple',
                'Others'
            ],

            'type' => [
                'Owner',
                'Tenant',
                'Mixed',
                'Government',
                'Lease',
                'Trust',
                'Partnership',
                'Private Limited',
                'Public Limited',
                'Others'
            ]

        ];

        $value = trim((string) $value);

        foreach ($allowed[$field] as $item) {

            if (
                strcasecmp(
                    $item,
                    $value
                ) === 0
            ) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return [

            'inserted' =>
                $this->insertedCount,

            'updated' =>
                $this->updatedCount,

            'skipped' =>
                count($this->skippedRows),

            'skipped_details' =>
                $this->skippedRows,

        ];
    }
}