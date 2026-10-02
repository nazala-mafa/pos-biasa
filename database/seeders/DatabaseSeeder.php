<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the superadmin and the default POS outlet. Sample data is seeded separately with
     * `php artisan db:seed --class=MasterDataSeeder` from database/seeders/data/pos.xlsx.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            TeamSeeder::class,
        ]);
    }
}
