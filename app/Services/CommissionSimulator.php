<?php

namespace App\Services;

use App\DTOs\SimulationResult;
use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Models\Contract;
use App\Models\FormulaVersion;

class CommissionSimulator
{
    public function __construct(
        private readonly FormulaValidator $validator,
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    /**
     * @throws ParseException
     * @throws UndefinedVariableException
     * @throws CircularDependencyException
     */
    public function simulate(FormulaVersion $targetFormula): SimulationResult
    {
        $this->validator->validate($targetFormula->expression, $targetFormula->variables ?? []);

        $activeFormula = FormulaVersion::where('is_active', true)->first();
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
        $variableMap = [
            'AnnualUsage' => $contract->annual_usage,
            'ContractValue' => $contract->contract_value,
            'ContractLength' => $contract->contract_length,
            'RiskScore' => $contract->risk_score,
        ];

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
