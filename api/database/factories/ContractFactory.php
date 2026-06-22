<?php

namespace Database\Factories;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'annual_usage' => (float) fake()->numberBetween(5000, 500000),
            'contract_value' => (float) fake()->numberBetween(10000, 1000000),
            'contract_length' => fake()->numberBetween(12, 60),
            'risk_score' => fake()->randomFloat(2, 1.0, 10.0),
        ];
    }
}
