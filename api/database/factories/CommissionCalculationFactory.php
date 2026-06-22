<?php

namespace Database\Factories;

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionCalculation>
 */
class CommissionCalculationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $annualUsage = fake()->numberBetween(5000, 500000);
        $contractValue = fake()->numberBetween(10000, 1000000);
        $contractLength = fake()->numberBetween(12, 60);
        $riskScore = fake()->randomFloat(2, 1.0, 10.0);

        $result = fake()->randomFloat(4, 1000, 100000);

        return [
            'formula_version_id' => FormulaVersion::factory(),
            'contract_id' => Contract::factory(),
            'input_values' => [
                'annual_usage' => $annualUsage,
                'contract_value' => $contractValue,
                'contract_length' => $contractLength,
                'risk_score' => $riskScore,
            ],
            'calculation_steps' => [
                [
                    'variable' => 'annual_usage',
                    'expression' => 'annual_usage',
                    'value' => $annualUsage,
                ],
                [
                    'variable' => 'contract_length',
                    'expression' => 'contract_length',
                    'value' => $contractLength,
                ],
                [
                    'variable' => CommissionCalculation::RESULT,
                    'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
                    'value' => $result,
                ],
            ],
            'result' => $result,
            'calculated_at' => now(),
        ];
    }
}
