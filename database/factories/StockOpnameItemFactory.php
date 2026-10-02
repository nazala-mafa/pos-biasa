<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpnameItem>
 */
class StockOpnameItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => StockOpname::findOrFail($attributes['stock_opname_id'])->team_id,
            ])->id,
            'system_quantity' => 10,
            'counted_quantity' => 8,
            'difference' => -2,
        ];
    }
}
