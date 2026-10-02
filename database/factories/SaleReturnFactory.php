<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class SaleReturnFactory extends Factory
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
            'sale_id' => fn (array $attributes) => Sale::factory()->create(['team_id' => $attributes['team_id']])->id,
            'number' => 'RET-'.fake()->unique()->numerify('########-####'),
            'total' => 0,
        ];
    }
}
