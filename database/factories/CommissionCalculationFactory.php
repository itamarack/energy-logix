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
                'AnnualUsage' => $annualUsage,
                'ContractValue' => $contractValue,
                'ContractLength' => $contractLength,
                'RiskScore' => $riskScore,
            ],
            'calculation_steps' => [
                [
                    'variable' => 'AnnualUsage',
                    'expression' => 'AnnualUsage',
                    'value' => $annualUsage,
                ],
                [
                    'variable' => 'ContractLength',
                    'expression' => 'ContractLength',
                    'value' => $contractLength,
                ],
                [
                    'variable' => 'RESULT',
                    'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
                    'value' => $result,
                ],
            ],
            'result' => $result,
            'calculated_at' => now(),
        ];
    }
}
