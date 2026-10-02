<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => null,
            'user_id' => null,
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'is_json' => false,
        ];
    }

    public function forTeam(?Team $team = null): static
    {
        return $this->state(fn () => ['team_id' => $team?->id ?? Team::factory()]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn () => ['user_id' => $user?->id ?? User::factory()]);
    }

    public function json(array $value = ['theme' => 'dark']): static
    {
        return $this->state(fn () => ['value' => json_encode($value), 'is_json' => true]);
    }
}
