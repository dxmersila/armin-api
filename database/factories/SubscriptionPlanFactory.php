<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                '1 Month',
                '3 Months',
                '6 Months',
                '1 Year',
            ]),
            'duration_months' => fake()->randomElement([
                1,
                3,
                6,
                12,
            ]),
            'price' => fake()->numberBetween(100000, 1000000),
            'is_active' => false,
        ];
    }
}
