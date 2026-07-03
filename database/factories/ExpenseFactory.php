<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'lease_id' => null,
            'title' => fake()->randomElement(['Reparație instalație', 'Întreținere lunară', 'Asigurare locuință']),
            'category' => fake()->randomElement(['maintenance', 'utilities', 'taxes', 'insurance', 'admin', 'repairs', 'other']),
            'amount' => fake()->randomFloat(2, 100, 2500),
            'currency' => 'RON',
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'paid_by' => 'owner',
            'responsible_party' => 'owner',
            'settlement_type' => 'none',
            'status' => fake()->randomElement(['paid', 'pending', 'reimbursable', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
