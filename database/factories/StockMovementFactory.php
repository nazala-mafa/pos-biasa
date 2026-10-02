<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
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
            'product_id' => fn (array $attributes) => Product::factory()->create(['team_id' => $attributes['team_id']])->id,
            'type' => StockMovementType::In,
            'quantity' => fake()->numberBetween(1, 50),
            'note' => fake()->sentence(),
        ];
    }
}
