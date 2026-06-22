<?php

namespace App\Services;

use App\DTOs\SimulationResult;
use App\Enums\FormulaVariable;
use App\Models\Contract;
use App\Models\FormulaVersion;

class CommissionSimulator
{
    public function __construct(
        private readonly FormulaValidator $validator,
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function simulate(FormulaVersion $targetFormula): SimulationResult
    {
        $this->validator->validate($targetFormula->expression, $targetFormula->variables ?? []);

        $activeFormula = FormulaVersion::query()->where('is_active', true)->first();
        $contracts = Contract::all();

        $currentTotal = 0.0;
        $newTotal = 0.0;

        foreach ($contracts as $contract) {
            if ($activeFormula !== null) {
                $currentTotal += $this->calculateDryRun($activeFormula, $contract);
            }

            $newTotal += $this->calculateDryRun($targetFormula, $contract);
        }

        return new SimulationResult(
            affected_contract_count: $contracts->count(),
            current_total_commission: $currentTotal,
            new_total_commission: $newTotal,
            difference: $newTotal - $currentTotal,
        );
    }

    private function calculateDryRun(FormulaVersion $formula, Contract $contract): float
    {
        $variableMap = $contract->only(FormulaVariable::values());

        $orderedNames = $this->resolver->resolve($formula->variables ?? []);

        $variablesByName = [];
        foreach ($formula->variables ?? [] as $variable) {
            $variablesByName[$variable['name']] = $variable['expression'];
        }

        foreach ($orderedNames as $name) {
            $value = $this->evaluator->evaluate($variablesByName[$name], $variableMap);
            $variableMap[$name] = $value;
        }

        return $this->evaluator->evaluate($formula->expression, $variableMap);
    }
}
