<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
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
            'name' => fake()->streetName().' Apartment',
            'type' => fake()->randomElement(['studio', 'apartment', 'house', 'commercial_space', 'office', 'other']),
            'country' => 'Romania',
            'city' => fake()->city(),
            'county_or_sector' => fake()->optional()->randomElement(['Cluj', 'Ilfov', 'Sector 1', 'Sector 2']),
            'address_line' => fake()->streetAddress(),
            'postal_code' => fake()->optional()->postcode(),
            'rooms' => fake()->optional()->numberBetween(1, 5),
            'usable_area_sqm' => fake()->optional()->randomFloat(2, 20, 180),
            'floor' => fake()->optional()->numberBetween(0, 10),
            'total_floors' => fake()->optional()->numberBetween(1, 12),
            'status' => fake()->randomElement(['available', 'occupied', 'renovation', 'inactive']),
            'monthly_rent_amount' => fake()->optional()->randomFloat(2, 200, 2500),
            'currency' => 'RON',
            'deposit_amount' => fake()->optional()->randomFloat(2, 200, 2500),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
