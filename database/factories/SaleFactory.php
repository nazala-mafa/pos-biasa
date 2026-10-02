<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 10000, 500000);

        return [
            'team_id' => Team::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('########-####'),
            'payment_method' => PaymentMethod::Cash,
            'subtotal' => $total,
            'discount' => 0,
            'tax_total' => 0,
            'total' => $total,
            'paid' => $total,
            'change' => 0,
        ];
    }
}
