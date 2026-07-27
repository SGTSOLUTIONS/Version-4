<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MissingBillExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function collection()
    {
        return collect($this->data);
    }


    public function headings(): array
    {
        if (count($this->data) > 0) {
            return array_keys((array)$this->data[0]);
        }

        return [];
    }
}
