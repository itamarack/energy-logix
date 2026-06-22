<?php

namespace App\Actions;

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionCalculator;

class CalculateContractCommissionAction
{
    public function __construct(
        private readonly CommissionCalculator $calculator
    ) {}

    public function execute(FormulaVersion $formula, Contract $contract): CommissionCalculation
    {
        $data = $this->calculator->calculate($formula, $contract);

        return $contract->commissionCalculations()->create([
            'formula_version_id' => $formula->id,
            'input_values' => $data->inputValues,
            'calculation_steps' => $data->steps,
            'result' => $data->result,
            'calculated_at' => now(),
        ]);
    }
}
