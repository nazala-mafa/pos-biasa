<?php

namespace Database\Seeders;

use App\Actions\Outlets\ImportOutletData;
use App\Actions\Teams\AssignDefaultTeam;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Path of the workbook (one sheet per menu) holding the default POS outlet data.
     */
    public const FILE = 'seeders/data/pos.xlsx';

    /**
     * Seed the default POS outlet from the same workbook format used by outlet import/export.
     */
    public function run(ImportOutletData $importOutletData): void
    {
        $path = database_path(self::FILE);
        $outlet = Team::where('name', AssignDefaultTeam::NAME)->first();

        if ($outlet === null || ! is_file($path)) {
            $this->command->warn('Skipped: POS outlet or '.self::FILE.' not found.');

            return;
        }

        $import = $importOutletData->handle($outlet, $path);

        $this->command->info("Imported {$import->total()} row(s) into the POS outlet.");

        foreach ($import->failures as $failure) {
            $this->command->warn($failure);
        }
    }
}
