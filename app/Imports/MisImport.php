<?php

namespace App\Imports;

use App\Models\Corporation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MisImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $corporationId;
    protected $tableName;
    protected $skippedRows = [];
    protected $updatedRows = [];
    protected $insertedRows = [];

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "mis_" . $corporationId;
    }

    /**
     * Process the collection of rows
     */
    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception("MIS table for corporation {$this->corporationId} does not exist.");
        }

        foreach ($rows as $index => $row) {
            try {
                $assessment = trim($row['assessment'] ?? '');

                // Skip if assessment is empty
                if (empty($assessment)) {
                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'Assessment number is empty'
                    ];
                    continue;
                }

                // Check if record exists
                $existingRecord = DB::table($this->tableName)
                    ->where('corporation_id', $this->corporationId)
                    ->where('assessment', $assessment)
                    ->first();

                $data = [
                    'corporation_id' => $this->corporationId,
                    'gisid' => $row['gisid'] ?? null,
                    'ward_no' => $row['ward_no'] ?? null,
                    'assessment' => $assessment,
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

                if ($existingRecord) {
                    // Update existing record
                    DB::table($this->tableName)
                        ->where('id', $existingRecord->id)
                        ->update(array_merge($data, ['updated_at' => now()]));

                    $this->updatedRows[] = $assessment;
                } else {
                    // Insert new record
                    DB::table($this->tableName)->insert(array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]));

                    $this->insertedRows[] = $assessment;
                }

            } catch (\Exception $e) {
                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage()
                ];
                Log::error("Error importing MIS row " . ($index + 2) . ": " . $e->getMessage());
            }
        }
    }

    /**
     * Parse decimal values from Excel
     */
    private function parseDecimal($value)
    {
        if (empty($value)) {
            return null;
        }

        // Remove any currency symbols and commas
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    /**
     * Validate and get enum value
     */
    private function validateEnumValue($value, $field)
    {
        if (empty($value)) {
            return null;
        }

        $allowedValues = [
            'usage' => [
                'Residential', 'Commercial', 'Industrial', 'Institutional',
                'Vacant', 'Agricultural', 'Mixed', 'Hospital', 'School',
                'Temple', 'Others'
            ],
            'type' => [
                'Owner', 'Tenant', 'Mixed', 'Government', 'Lease',
                'Trust', 'Partnership', 'Private Limited', 'Public Limited', 'Others'
            ]
        ];

        $normalized = trim($value);

        // Try to match case-insensitively
        $matched = collect($allowedValues[$field])->first(function($allowed) use ($normalized) {
            return strcasecmp($allowed, $normalized) === 0;
        });

        if ($matched) {
            return $matched;
        }

        // If no match found, return null or default
        return null;
    }

    /**
     * Define validation rules
     */
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
     * Get custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'assessment.max' => 'Assessment number must not exceed 100 characters',
            'plot_area.numeric' => 'Plot area must be a valid number',
            'half_year_tax.numeric' => 'Half year tax must be a valid number',
            'balance.numeric' => 'Balance must be a valid number',
        ];
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return [
            'inserted' => count($this->insertedRows),
            'updated' => count($this->updatedRows),
            'skipped' => count($this->skippedRows),
            'skipped_details' => $this->skippedRows,
            'inserted_assessments' => $this->insertedRows,
            'updated_assessments' => $this->updatedRows,
        ];
    }
}
