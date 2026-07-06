<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
                $rowNumber = $index + 2; // Excel row number (1-indexed + header)
                $ptNumber = trim($row['pt_number'] ?? '');

                if (empty($ptNumber)) {
                    $this->skippedRows[] = [
                        'row' => $rowNumber,
                        'reason' => 'PT Number is empty'
                    ];
                    continue;
                }

                // Parse dates - convert Excel serial numbers and invalid formats
                $dueDate = $this->parseDate($row['due_date'] ?? null);
                $paidDate = $this->parseDate($row['paid_date'] ?? null);

                // Skip rows with invalid dates
                if (!empty($row['due_date']) && $dueDate === null) {
                    $this->skippedRows[] = [
                        'row' => $rowNumber,
                        'reason' => "Invalid due_date format: '{$row['due_date']}'"
                    ];
                    continue;
                }

                if (!empty($row['paid_date']) && $paidDate === null) {
                    $this->skippedRows[] = [
                        'row' => $rowNumber,
                        'reason' => "Invalid paid_date format: '{$row['paid_date']}'"
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
                    'employee_count' => $this->parseInteger($row['employee_count'] ?? null),
                    'half_year_tax' => $this->parseDecimal($row['half_year_tax'] ?? null),
                    'arrears' => $this->parseDecimal($row['arrears'] ?? null) ?? 0,
                    'penalty' => $this->parseDecimal($row['penalty'] ?? null) ?? 0,
                    'balance' => $this->parseDecimal($row['balance'] ?? null) ?? 0,
                    'paid_amount' => $this->parseDecimal($row['paid_amount'] ?? null),
                    'payment_status' => $row['payment_status'] ?? null,
                    'payment_mode' => $row['payment_mode'] ?? null,
                    'receipt_number' => $row['receipt_number'] ?? null,
                    'tax_period' => $row['tax_period'] ?? null,
                    'due_date' => $dueDate,
                    'paid_date' => $paidDate,
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

                Log::error("Row " . ($index + 2) . " error: " . $e->getMessage());
            }
        }
    }

    /**
     * Parse decimal values with proper cleaning
     * Returns null for empty values, float for valid numbers
     */
    private function parseDecimal($value)
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        // Remove any non-numeric characters except decimal point and minus sign
        $cleaned = preg_replace('/[^0-9.-]/', '', (string)$value);

        if ($cleaned === '' || $cleaned === '-') {
            return null;
        }

        return (float)$cleaned;
    }

    /**
     * Parse integer values
     */
    private function parseInteger($value)
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9-]/', '', (string)$value);

        if ($cleaned === '' || $cleaned === '-') {
            return null;
        }

        return (int)$cleaned;
    }

    /**
     * Parse date from various formats including Excel serial numbers
     * Handles: Excel serial numbers (46295), d-m-Y, d/m/Y, Y-m-d, etc.
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string)$value);

        // Check if it's an Excel serial number (numeric like 46295)
        if (is_numeric($value) && strpos($value, '.') === false && strlen($value) >= 5) {
            try {
                $serial = (int)$value;
                // Excel serial date conversion
                // Excel serial 1 = 1900-01-01
                // Unix timestamp for 1900-01-01 is -2208988800
                // Excel serial 25569 = 1970-01-01
                if ($serial > 59) {
                    // Adjust for Excel's 1900 leap year bug
                    $unixTimestamp = ($serial - 25569) * 86400;
                } else {
                    $unixTimestamp = ($serial - 25570) * 86400;
                }

                $date = Carbon::createFromTimestamp($unixTimestamp);
                if ($date && $date->isValid()) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Not a valid Excel serial number, continue with other formats
            }
        }

        // Try different date formats
        $formats = [
            'd-m-Y',      // 30-09-2026
            'd/m/Y',      // 30/09/2026
            'Y-m-d',      // 2026-09-30
            'Y/m/d',      // 2026/09/30
            'm-d-Y',      // 09-30-2026
            'm/d/Y',      // 09/30/2026
            'd-M-Y',      // 30-Sep-2026
            'M-d-Y',      // Sep-30-2026
            'd.m.Y',      // 30.09.2026
            'Y.m.d',      // 2026.09.30
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->isValid()) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try to parse with Carbon automatically (for ISO formats)
        try {
            $date = Carbon::parse($value);
            if ($date && $date->isValid()) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Ignore and return null
        }

        // If none of the formats work, return null
        return null;
    }

    public function rules(): array
    {
        return [
            'pt_number' => 'nullable|max:100',
            'employee_count' => 'nullable|integer',
            'half_year_tax' => 'nullable|numeric',
            'arrears' => 'nullable|numeric',
            'penalty' => 'nullable|numeric',
            'balance' => 'nullable|numeric',
            'due_date' => 'nullable',
            'paid_date' => 'nullable',
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
