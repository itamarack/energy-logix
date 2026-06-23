<?php

namespace Database\Seeders;

use App\Models\FormulaVariable;
use Illuminate\Database\Seeder;

class FormulaVariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variables = [
            ['name' => 'annual_usage', 'description' => 'Annual usage in kWh'],
            ['name' => 'contract_value', 'description' => 'Total contract value in $'],
            ['name' => 'contract_length', 'description' => 'Duration in months'],
            ['name' => 'risk_score', 'description' => 'Risk factor 1–10'],
        ];

        foreach ($variables as $variable) {
            FormulaVariable::updateOrCreate(['name' => $variable['name']], $variable);
        }
    }
}
