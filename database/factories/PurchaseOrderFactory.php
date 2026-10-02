<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
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
            'supplier_id' => fn (array $attributes) => Supplier::factory()->create(['team_id' => $attributes['team_id']])->id,
            'number' => 'PO-'.fake()->unique()->numerify('########-####'),
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_at' => now()->toDateString(),
            'total' => 0,
        ];
    }
}
