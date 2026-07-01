<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UgdTaxImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $corporationId;
    protected $tableName;
    protected $skippedRows = [];
    protected $updatedRows = [];
    protected $insertedRows = [];

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "ugd_tax_" . $corporationId;
    }

    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception("UGD Tax table for corporation {$this->corporationId} does not exist.");
        }

        foreach ($rows as $index => $row) {
            try {

                $ugdNo = trim($row['ugd_no'] ?? '');

                if (empty($ugdNo)) {
                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'UGD Number is empty'
                    ];
                    continue;
                }

                $existingRecord = DB::table($this->tableName)
                    ->where('corporation_id', $this->corporationId)
                    ->where('ugd_no', $ugdNo)
                    ->first();

                $data = [
                    'corporation_id' => $this->corporationId,
                    'gisid' => $row['gisid'] ?? null,
                    'ward_no' => $row['ward_no'] ?? null,
                    'assessment' => $row['assessment'] ?? null,
                    'road_name' => $row['road_name'] ?? null,
                    'ugd_no' => $ugdNo,
                    'old_ugd_no' => $row['old_ugd_no'] ?? null,
                    'old_door_no' => $row['old_door_no'] ?? null,
                    'new_door_no' => $row['new_door_no'] ?? null,
                    'owner_name' => $row['owner_name'] ?? null,
                    'phone_number' => $row['phone_number'] ?? null,

                    'slab_rate' => $this->parseDecimal($row['slab_rate'] ?? null),
                    'balance' => $this->parseDecimal($row['balance'] ?? null),

                    'usage' => $row['usage'] ?? null,
                    'slab_description' => $row['slab_description'] ?? null,
                    'dbc_type' => $row['dbc_type'] ?? null,
                    'tax_year' => $row['tax_year'] ?? null,

                    'ugd_tax_amount' => $this->parseDecimal($row['ugd_tax_amount'] ?? null),
                    'ugd_tax_due' => $this->parseDecimal($row['ugd_tax_due'] ?? null),
                    'ugd_tax_paid' => $this->parseDecimal($row['ugd_tax_paid'] ?? null),

                    'ugd_tax_paid_date' => $row['ugd_tax_paid_date'] ?? null,
                    'payment_mode' => $row['payment_mode'] ?? null,
                    'receipt_number' => $row['receipt_number'] ?? null,
                    'due_date' => $row['due_date'] ?? null,

                    'status' => $row['status'] ?? 'Active',
                    'remarks' => $row['remarks'] ?? null,
                ];

                if ($existingRecord) {

                    DB::table($this->tableName)
                        ->where('id', $existingRecord->id)
                        ->update(array_merge($data, [
                            'updated_at' => now()
                        ]));

                    $this->updatedRows[] = $ugdNo;

                } else {

                    DB::table($this->tableName)
                        ->insert(array_merge($data, [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]));

                    $this->insertedRows[] = $ugdNo;
                }

            } catch (\Exception $e) {

                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage()
                ];

                Log::error(
                    "UGD Import Row " . ($index + 2) . ": " . $e->getMessage()
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

    public function rules(): array
    {
        return [
            'ugd_no' => 'nullable|string|max:100',
            'assessment' => 'nullable|string|max:100',
            'owner_name' => 'nullable|string|max:255',
            'slab_rate' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'ugd_tax_amount' => 'nullable|numeric',
            'ugd_tax_due' => 'nullable|numeric',
            'ugd_tax_paid' => 'nullable|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'ugd_no.max' => 'UGD Number must not exceed 100 characters',
            'slab_rate.numeric' => 'Slab Rate must be numeric',
            'balance.numeric' => 'Balance must be numeric',
            'ugd_tax_amount.numeric' => 'UGD Tax Amount must be numeric',
            'ugd_tax_due.numeric' => 'UGD Tax Due must be numeric',
            'ugd_tax_paid.numeric' => 'UGD Tax Paid must be numeric',
        ];
    }

    public function getStats()
    {
        return [
            'inserted' => count($this->insertedRows),
            'updated' => count($this->updatedRows),
            'skipped' => count($this->skippedRows),
            'skipped_details' => $this->skippedRows,
            'inserted_ugd_numbers' => $this->insertedRows,
            'updated_ugd_numbers' => $this->updatedRows,
        ];
    }
}
