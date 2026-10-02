<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Hands the rows of one sheet to {@see OutletDataImport}, which applies them in dependency order.
 */
class OutletDataSheetImport implements SkipsEmptyRows, ToCollection, WithCustomCsvSettings, WithHeadingRow
{
    public function __construct(private OutletDataImport $import, private string $sheet) {}

    /**
     * @return array<string, string>
     */
    public function getCsvSettings(): array
    {
        return ['delimiter' => ','];
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        $this->import->rows[$this->sheet] = $collection->map(fn (Collection $row) => $row->all())->all();
    }
}
