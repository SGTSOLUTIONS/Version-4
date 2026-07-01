<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class WaterTaxImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $corporationId;
    protected $tableName;
    protected $skippedRows = [];
    protected $updatedRows = [];
    protected $insertedRows = [];

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "water_tax_" . $corporationId;
    }

    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception("Water Tax table for corporation {$this->corporationId} does not exist.");
        }

        foreach ($rows as $index => $row) {

            try {

                $waterTaxNo = trim($row['watertax_no'] ?? '');

                if (empty($waterTaxNo)) {
                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'Water Tax Number is empty'
                    ];
                    continue;
                }

                $existingRecord = DB::table($this->tableName)
                    ->where('corporation_id', $this->corporationId)
                    ->where('watertax_no', $waterTaxNo)
                    ->first();

                $data = [
                    'corporation_id' => $this->corporationId,
                    'gisid' => $row['gisid'] ?? null,
                    'ward_no' => $row['ward_no'] ?? null,
                    'assessment' => $row['assessment'] ?? null,
                    'road_name' => $row['road_name'] ?? null,
                    'watertax_no' => $waterTaxNo,
                    'old_watertax_no' => $row['old_watertax_no'] ?? null,
                    'old_door_no' => $row['old_door_no'] ?? null,
                    'new_door_no' => $row['new_door_no'] ?? null,
                    'phone_number' => $row['phone_number'] ?? null,

                    'slab_rate' => $this->parseDecimal($row['slab_rate'] ?? null),
                    'balance' => $this->parseDecimal($row['balance'] ?? null),

                    'usage' => $this->validateUsage($row['usage'] ?? null),
                    'slab_description' => $row['slab_description'] ?? null,
                    'DBC_type' => $this->validateDbcType($row['dbc_type'] ?? $row['DBC_type'] ?? null),
                ];

                if ($existingRecord) {

                    DB::table($this->tableName)
                        ->where('id', $existingRecord->id)
                        ->update(array_merge($data, [
                            'updated_at' => now()
                        ]));

                    $this->updatedRows[] = $waterTaxNo;

                } else {

                    DB::table($this->tableName)
                        ->insert(array_merge($data, [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]));

                    $this->insertedRows[] = $waterTaxNo;
                }

            } catch (\Exception $e) {

                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage()
                ];

                Log::error(
                    "Water Tax Import Row " . ($index + 2) . ": " . $e->getMessage()
                );
            }
        }
    }

    private function parseDecimal($value)
    {
        if (empty($value)) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', $value);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function validateUsage($value)
    {
        if (empty($value)) {
            return null;
        }

        $allowed = [
            'Residential',
            'Commercial',
            'Industrial',
            'Institutional',
            'Vacant',
            'Others'
        ];

        $matched = collect($allowed)->first(function ($item) use ($value) {
            return strcasecmp(trim($item), trim($value)) === 0;
        });

        return $matched ?: null;
    }

    private function validateDbcType($value)
    {
        if (empty($value)) {
            return null;
        }

        $allowed = [
            'Owner',
            'Tenant',
            'Mixed',
            'Government',
            'Others'
        ];

        $matched = collect($allowed)->first(function ($item) use ($value) {
            return strcasecmp(trim($item), trim($value)) === 0;
        });

        return $matched ?: null;
    }

    public function rules(): array
    {
        return [
            'watertax_no' => 'nullable|string|max:100',
            'assessment' => 'nullable|string|max:100',
            'slab_rate' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'watertax_no.max' => 'Water Tax Number must not exceed 100 characters',
            'slab_rate.numeric' => 'Slab Rate must be numeric',
            'balance.numeric' => 'Balance must be numeric',
        ];
    }

    public function getStats()
    {
        return [
            'inserted' => count($this->insertedRows),
            'updated' => count($this->updatedRows),
            'skipped' => count($this->skippedRows),
            'skipped_details' => $this->skippedRows,
            'inserted_water_tax_numbers' => $this->insertedRows,
            'updated_water_tax_numbers' => $this->updatedRows,
        ];
    }
}
