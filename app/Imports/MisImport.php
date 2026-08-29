<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Collection;

class MisImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $corporationId;
    protected $tableName;
    protected $skippedRows = [];
    protected $updatedRows = [];
    protected $insertedRows = [];
    protected $batchData = [];
    protected $batchSize = 1000;

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "mis_" . $corporationId;
    }

    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception("MIS table {$this->tableName} not found.");
        }

        $this->batchData = [];
        $index = 1;

        foreach ($rows as $row) {
            try {
                $assessment = trim($row['assessment'] ?? '');
                if ($assessment == '') {
                    $this->skippedRows[] = ['row' => $index, 'reason' => 'Assessment Empty'];
                    $index++;
                    continue;
                }

                $data = $this->prepareData($row);

                $exists = DB::table($this->tableName)
                    ->where('corporation_id', $this->corporationId)
                    ->where('assessment', $assessment)
                    ->first();

                if ($exists) {
                    DB::table($this->tableName)
                        ->where('id', $exists->id)
                        ->update(array_merge($data, ['updated_at' => now()]));
                    $this->updatedRows[] = $assessment;
                } else {
                    $this->batchData[] = array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $this->insertedRows[] = $assessment;

                    // Insert in batches
                    if (count($this->batchData) >= $this->batchSize) {
                        $this->flushBatch();
                    }
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $this->skippedRows[] = ['row' => $index, 'reason' => $e->getMessage()];
            }
            $index++;
        }

        // Insert remaining rows
        $this->flushBatch();
    }

    protected function prepareData($row)
    {
        return [
            'corporation_id' => $this->corporationId,
            'gisid' => $row['gisid'] ?? null,
            'ward_no' => $row['ward_no'] ?? null,
            'assessment' => trim($row['assessment'] ?? ''),
            'old_assessment' => $row['old_assessment'] ?? null,
            'road_name' => $row['road_name'] ?? null,
            'owner_name' => $row['owner_name'] ?? null,
            'old_door_no' => $row['old_door_no'] ?? null,
            'new_door_no' => $row['new_door_no'] ?? null,
            'phone_number' => $row['phone_number'] ?? null,
            'plot_area' => $this->parseDecimal($row['plot_area'] ?? null),
            'half_year_tax' => $this->parseDecimal($row['half_year_tax'] ?? null),
            'balance' => $this->parseDecimal($row['balance'] ?? null),
            'usage' => $this->validateEnumValue($row['usage'] ?? null, 'usage'),
            'type' => $this->validateEnumValue($row['type'] ?? null, 'type'),
            'zone' => $row['zone'] ?? null,
        ];
    }

    protected function flushBatch()
    {
        if (!empty($this->batchData)) {
            DB::table($this->tableName)->insert($this->batchData);
            $this->batchData = [];
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    private function parseDecimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) preg_replace('/[^0-9.\-]/', '', $value);
    }

    private function validateEnumValue($value, $field)
    {
        if (!$value) {
            return null;
        }

        $allowed = [
            'usage' => [
                'Residential', 'Commercial', 'Industrial', 'Institutional',
                'Vacant', 'Agricultural', 'Mixed', 'Hospital',
                'School', 'Temple', 'Others'
            ],
            'type' => [
                'Owner', 'Tenant', 'Mixed', 'Government', 'Lease',
                'Trust', 'Partnership', 'Private Limited',
                'Public Limited', 'Others'
            ]
        ];

        foreach ($allowed[$field] as $item) {
            if (strcasecmp($item, trim($value)) == 0) {
                return $item;
            }
        }
        return null;
    }

    public function getStats()
    {
        return [
            'inserted' => count($this->insertedRows),
            'updated' => count($this->updatedRows),
            'skipped' => count($this->skippedRows),
            'skipped_details' => $this->skippedRows,
        ];
    }
}