<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MisImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $corporationId;
    protected $tableName;

    public function __construct($corporationId)
    {
        $this->corporationId = $corporationId;
        $this->tableName = "mis_" . $corporationId;
    }

    public function collection(Collection $rows)
    {
        $data = [];
        $now = now();

        foreach ($rows as $row) {

            if (empty($row['assessment'])) {
                continue;
            }

            $data[] = [
                'corporation_id' => $this->corporationId,

                'gisid'          => $row['gisid'] ?? null,
                'ward_no'        => $row['ward_no'] ?? null,
                'assessment'     => trim($row['assessment']),
                'old_assessment' => $row['old_assessment'] ?? null,
                'road_name'      => $row['road_name'] ?? null,
                'owner_name'     => $row['owner_name'] ?? null,
                'old_door_no'    => $row['old_door_no'] ?? null,
                'new_door_no'    => $row['new_door_no'] ?? null,
                'phone_number'   => $row['phone_number'] ?? null,

                'plot_area'      => $this->decimal($row['plot_area'] ?? null),
                'half_year_tax'  => $this->decimal($row['half_year_tax'] ?? null),
                'balance'        => $this->decimal($row['balance'] ?? null),

                'usage'          => $row['usage'] ?? null,
                'type'           => $row['type'] ?? null,
                'zone'           => $row['zone'] ?? null,

                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (empty($data)) {
            return;
        }

        DB::table($this->tableName)->upsert(
            $data,
            ['corporation_id', 'assessment'],
            [
                'gisid',
                'ward_no',
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
                'updated_at',
            ]
        );
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    private function decimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $value);
    }
}
