<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutletsTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [['Cabang Bandung']];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['name'];
    }
}
