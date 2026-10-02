<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $unitCost = fake()->numberBetween(1000, 50000);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => PurchaseOrder::query()->whereKey($attributes['purchase_order_id'])->firstOrFail()->team_id,
            ])->id,
            'quantity' => $quantity,
            'received_quantity' => 0,
            'unit_cost' => $unitCost,
            'subtotal' => $quantity * $unitCost,
        ];
    }
}
