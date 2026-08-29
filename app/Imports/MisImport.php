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

    // Keep this SMALL to avoid MySQL 1390
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
        // Check table
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception(
                "MIS table {$this->tableName} not found."
            );
        }

        $batch = [];

        foreach ($rows as $index => $row) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Assessment
                |--------------------------------------------------------------------------
                */

                $assessment = trim(
                    (string) ($row['assessment'] ?? '')
                );

                /*
                |--------------------------------------------------------------------------
                | Ward
                |--------------------------------------------------------------------------
                */

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
                | Bulk Upsert
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
        | Remaining Rows
        |--------------------------------------------------------------------------
        */

        if (!empty($batch)) {

            $this->upsertBatch($batch);

            $batch = [];
        }
    }

    /**
     * Prepare one row
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
     * Bulk insert/update
     */
    protected function upsertBatch(array $batch)
    {
        if (empty($batch)) {
            return;
        }

        DB::table($this->tableName)->upsert(

            $batch,

            /*
            |--------------------------------------------------------------------------
            | MATCHING KEY
            |--------------------------------------------------------------------------
            |
            | Same corporation + ward + assessment
            | = UPDATE
            |
            | Otherwise
            | = INSERT
            |
            */

            [
                'corporation_id',
                'ward_no',
                'assessment'
            ],

            /*
            |--------------------------------------------------------------------------
            | Columns to UPDATE
            |--------------------------------------------------------------------------
            */

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
     * Parse decimal values
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
     * Import statistics
     */
    public function getStats()
    {
        return [

            'skipped' =>
                count($this->skippedRows),

            'skipped_details' =>
                $this->skippedRows,

        ];
    }
}