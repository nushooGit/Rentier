<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
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
            'property_id' => fn (array $attributes) => Property::factory()->state([
                'team_id' => $attributes['team_id'],
            ]),
            'renter_id' => fn (array $attributes) => Renter::factory()->state([
                'team_id' => $attributes['team_id'],
            ]),
            'start_date' => fake()->dateTimeBetween('-1 year', '+1 month')->format('Y-m-d'),
            'end_date' => fake()->boolean()
                ? fake()->dateTimeBetween('+2 months', '+2 years')->format('Y-m-d')
                : null,
            'monthly_rent_amount' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'RON',
            'rent_due_day' => fake()->numberBetween(1, 31),
            'deposit_amount' => fake()->optional()->randomFloat(2, 500, 5000),
            'status' => fake()->randomElement(['upcoming', 'active', 'ended', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
