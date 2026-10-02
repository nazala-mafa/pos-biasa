<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed the default master data for every outlet.
     */
    public function run(): void
    {
        Team::where('is_personal', false)->each(function (Team $outlet) {
            foreach (['Food', 'Beverage', 'Other'] as $name) {
                $outlet->productCategories()->firstOrCreate(['name' => $name]);
            }

            foreach (['Piece' => 'pcs', 'Kilogram' => 'kg', 'Liter' => 'l'] as $name => $abbreviation) {
                $outlet->units()->firstOrCreate(['name' => $name], ['abbreviation' => $abbreviation]);
            }

            $outlet->taxes()->firstOrCreate(['name' => 'PPN'], ['rate' => 11]);

            foreach (['Retail', 'Wholesale'] as $name) {
                $outlet->prices()->firstOrCreate(['name' => $name]);
            }
        });
    }
}
