<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1000, 100000);

        return [
            'sale_id' => Sale::factory(),
            'sku' => strtoupper(fake()->bothify('SKU-####??')),
            'name' => fake()->words(3, true),
            'quantity' => 1,
            'unit_price' => $price,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => $price,
        ];
    }
}
