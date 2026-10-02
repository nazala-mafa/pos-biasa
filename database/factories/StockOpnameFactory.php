<?php

namespace Database\Factories;

use App\Models\StockOpname;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'number' => 'SO-'.fake()->unique()->numerify('########-####'),
            'counted_at' => now()->toDateString(),
        ];
    }
}
