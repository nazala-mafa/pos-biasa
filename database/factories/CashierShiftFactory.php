<?php

namespace Database\Factories;

use App\Models\CashierShift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierShift>
 */
class CashierShiftFactory extends Factory
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
            'user_id' => User::factory(),
            'opened_at' => now(),
            'opening_cash' => 100000,
        ];
    }
}
