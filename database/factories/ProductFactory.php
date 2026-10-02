<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'cost_price' => fake()->randomFloat(2, 1000, 50000),
            'selling_price' => fake()->randomFloat(2, 50000, 100000),
            'min_stock' => 5,
            'is_active' => true,
        ];
    }
}
