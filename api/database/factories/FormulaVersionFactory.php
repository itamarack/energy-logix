<?php

namespace Database\Factories;

use App\Models\FormulaVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormulaVersion>
 */
class FormulaVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Commission '.fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']).' '.fake()->year(),
            'description' => fake()->optional()->sentence(),
            'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
            'variables' => [],
            'is_active' => false,
            'version_number' => $this->faker->unique()->numberBetween(1, 1000),
        ];
    }

    /**
     * Indicate that this formula version is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
