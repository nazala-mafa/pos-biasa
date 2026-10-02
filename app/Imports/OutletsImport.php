<?php

namespace App\Imports;

use App\Models\Team;
use App\Rules\TeamName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OutletsImport implements SkipsEmptyRows, ToCollection, WithCustomCsvSettings, WithHeadingRow
{
    public int $imported = 0;

    /**
     * Row-level failures as "row number => first error message".
     *
     * @var array<int, string>
     */
    public array $failures = [];

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
        $seen = [];

        foreach ($collection as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $line = $index + 2;

            $validator = Validator::make(['name' => $name], [
                'name' => [
                    'required', 'string', 'max:255', new TeamName,
                    Rule::unique('teams', 'name')->whereNull('deleted_at'),
                ],
            ]);

            if ($validator->fails()) {
                $this->failures[$line] = $validator->errors()->first('name');

                continue;
            }

            if (isset($seen[mb_strtolower($name)])) {
                $this->failures[$line] = __('Duplicate name in file.');

                continue;
            }

            $seen[mb_strtolower($name)] = true;

            Team::create(['name' => $name]);
            $this->imported++;
        }
    }
}
