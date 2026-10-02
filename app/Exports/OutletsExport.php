<?php

namespace App\Exports;

use App\Models\Team;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Team>
 */
class OutletsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, Team>
     */
    public function collection(): Collection
    {
        return Team::query()
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['name', 'users', 'created_at'];
    }

    /**
     * @param  Team  $row
     * @return array<int, int|string|null>
     */
    public function map($row): array
    {
        return [$row->name, $row->members_count, $row->created_at?->toDateString()];
    }
}
