<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MissingBillExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
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
            return array_keys((array)$this->data->first());
        }

        return [];
    }


    public function styles(Worksheet $sheet)
    {

        // Heading style
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->applyFromArray([

                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],

                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],

            ]);


        // All data border
        $sheet->getStyle(
            'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow()
        )
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);


        // Row height for heading
        $sheet->getRowDimension(1)->setRowHeight(25);


        return [];
    }
}
