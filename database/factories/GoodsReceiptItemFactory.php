<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'product_id' => fn (array $attributes) => PurchaseOrderItem::findOrFail($attributes['purchase_order_item_id'])->product_id,
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
