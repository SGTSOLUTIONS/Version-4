<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ProfessionalTaxImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $corporationId;
    protected $tableName;
    protected $skippedRows = [];
    protected $updatedRows = [];
    protected $insertedRows = [];

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "professional_tax_" . $corporationId;
    }

    public function collection(Collection $rows)
    {
        if (!Schema::hasTable($this->tableName)) {
            throw new \Exception("Professional Tax table does not exist.");
        }

        foreach ($rows as $index => $row) {
            try {

                $ptNumber = trim($row['pt_number'] ?? '');

                if (empty($ptNumber)) {
                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'PT Number is empty'
                    ];
                    continue;
                }

                $existingRecord = DB::table($this->tableName)
                    ->where('corporation_id', $this->corporationId)
                    ->where('pt_number', $ptNumber)
                    ->first();

                $data = [
                    'corporation_id' => $this->corporationId,
                    'gisid' => $row['gisid'] ?? null,
                    'ward_no' => $row['ward_no'] ?? null,
                    'assessment' => $row['assessment'] ?? null,
                    'pt_number' => $ptNumber,
                    'old_pt_number' => $row['old_pt_number'] ?? null,
                    'establishment_name' => $row['establishment_name'] ?? null,
                    'owner_name' => $row['owner_name'] ?? null,
                    'phone_number' => $row['phone_number'] ?? null,
                    'profession_type' => $row['profession_type'] ?? null,
                    'employee_count' => $row['employee_count'] ?? null,
                    'half_year_tax' => $this->parseDecimal($row['half_year_tax'] ?? null),
                    'arrears' => $this->parseDecimal($row['arrears'] ?? null),
                    'penalty' => $this->parseDecimal($row['penalty'] ?? null),
                    'balance' => $this->parseDecimal($row['balance'] ?? null),
                    'paid_amount' => $this->parseDecimal($row['paid_amount'] ?? null),
                    'payment_status' => $row['payment_status'] ?? null,
                    'payment_mode' => $row['payment_mode'] ?? null,
                    'receipt_number' => $row['receipt_number'] ?? null,
                    'tax_period' => $row['tax_period'] ?? null,
                    'due_date' => $row['due_date'] ?? null,
                    'paid_date' => $row['paid_date'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                ];

                if ($existingRecord) {
                    DB::table($this->tableName)
                        ->where('id', $existingRecord->id)
                        ->update(array_merge($data, [
                            'updated_at' => now()
                        ]));

                    $this->updatedRows[] = $ptNumber;
                } else {

                    DB::table($this->tableName)
                        ->insert(array_merge($data, [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]));

                    $this->insertedRows[] = $ptNumber;
                }

            } catch (\Exception $e) {

                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage()
                ];

                Log::error($e->getMessage());
            }
        }
    }

    private function parseDecimal($value)
    {
        if (empty($value)) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', $value);

        return $cleaned !== '' ? (float)$cleaned : null;
    }

    public function rules(): array
    {
        return [
            'pt_number' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'half_year_tax' => 'nullable|numeric',
            'arrears' => 'nullable|numeric',
            'penalty' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
        ];
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
