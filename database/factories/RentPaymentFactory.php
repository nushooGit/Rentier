<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentPayment>
 */
class RentPaymentFactory extends Factory
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
            'lease_id' => fn (array $attributes) => Lease::factory()->state([
                'team_id' => $attributes['team_id'],
            ]),
            'property_id' => fn (array $attributes) => Lease::query()
                ->whereKey($attributes['lease_id'])
                ->value('property_id'),
            'renter_id' => fn (array $attributes) => Lease::query()
                ->whereKey($attributes['lease_id'])
                ->value('renter_id'),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'RON',
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'period_month' => fake()->numberBetween(1, 12),
            'period_year' => fake()->numberBetween(2024, 2026),
            'method' => fake()->optional()->randomElement(['cash', 'bank_transfer', 'card', 'other']),
            'status' => fake()->randomElement(['paid', 'partial', 'pending', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
