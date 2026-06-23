<?php

namespace Database\Seeders;

use App\Actions\CalculateContractCommissionAction;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Database\Seeder;

class CommissionCalculationSeeder extends Seeder
{
    public function __construct(
        private readonly CalculateContractCommissionAction $calculateAction
    ) {}

    public function run(): void
    {
        $formula = FormulaVersion::query()->where('is_active', true)->first();

        $contracts = Contract::take(10)->get();

        foreach ($contracts as $contract) {
            $this->calculateAction->execute($formula, $contract);
        }
    }
}
