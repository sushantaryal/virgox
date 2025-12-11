<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'symbol' => fake()->randomElement(['BTC', 'ETH']),
            'amount' => fake()->numberBetween(0, 100),
            'locked_amount' => fake()->numberBetween(0, 50),
        ];
    }

    /**
     * Indicate that the model's user_id should be associated with a new user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->create()->id,
        ]);
    }
}
