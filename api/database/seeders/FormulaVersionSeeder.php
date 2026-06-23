<?php

namespace Database\Seeders;

use App\Models\FormulaVersion;
use Illuminate\Database\Seeder;

class FormulaVersionSeeder extends Seeder
{
    public function run(): void
    {
        FormulaVersion::create([
            'name' => 'Initial Commission Structure',
            'description' => 'Simple flat rate commission based on annual usage.',
            'version_number' => 1,
            'expression' => 'annual_usage * 0.05',
            'variables' => [],
            'is_active' => false,
        ]);

        FormulaVersion::create([
            'name' => 'Q2 Commission Update',
            'description' => 'Added a flat bonus based on the contract length.',
            'version_number' => 2,
            'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
            'variables' => [],
            'is_active' => false,
        ]);

        FormulaVersion::create([
            'name' => 'Current Commission Structure',
            'description' => 'Advanced commission incorporating risk score adjustments.',
            'version_number' => 3,
            'expression' => 'BaseCommission + LengthBonus - RiskPenalty',
            'variables' => [
                [
                    'name' => 'BaseCommission',
                    'expression' => 'annual_usage * 0.05',
                ],
                [
                    'name' => 'LengthBonus',
                    'expression' => 'contract_length * 50',
                ],
                [
                    'name' => 'RiskPenalty',
                    'expression' => 'risk_score * 100',
                ],
            ],
            'is_active' => true,
        ]);
    }
}
