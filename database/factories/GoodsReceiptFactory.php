<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
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
            'purchase_order_id' => fn (array $attributes) => PurchaseOrder::factory()->create(['team_id' => $attributes['team_id']])->id,
            'number' => 'GR-'.fake()->unique()->numerify('########-####'),
            'received_at' => now()->toDateString(),
        ];
    }
}
