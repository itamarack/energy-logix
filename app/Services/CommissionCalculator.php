<?php

namespace App\Services;

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

class CommissionCalculator
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function calculate(FormulaVersion $formula, Contract $contract): CommissionCalculation
    {
        $variableMap = $this->buildVariableMap($contract);

        $orderedNames = $this->resolver->resolve($formula->variables);

        $variablesByName = [];
        foreach ($formula->variables as $variable) {
            $variablesByName[$variable['name']] = $variable['expression'];
        }

        $steps = [];
        foreach ($orderedNames as $name) {
            $expression = $variablesByName[$name];
            $value = $this->evaluator->evaluate($expression, $variableMap);

            $steps[] = [
                'variable' => $name,
                'expression' => $expression,
                'value' => $value,
            ];

            $variableMap[$name] = $value;
        }

        $result = $this->evaluator->evaluate($formula->expression, $variableMap);

        $steps[] = [
            'variable' => 'RESULT',
            'expression' => $formula->expression,
            'value' => $result,
        ];

        return CommissionCalculation::create([
            'formula_version_id' => $formula->id,
            'contract_id' => $contract->id,
            'input_values' => $this->buildInputValues($contract),
            'calculation_steps' => $steps,
            'result' => $result,
            'calculated_at' => now(),
        ]);
    }

    private function buildVariableMap(Contract $contract): array
    {
        return [
            'AnnualUsage' => $contract->annual_usage,
            'ContractValue' => $contract->contract_value,
            'ContractLength' => $contract->contract_length,
            'RiskScore' => $contract->risk_score,
        ];
    }

    private function buildInputValues(Contract $contract): array
    {
        return [
            'AnnualUsage' => $contract->annual_usage,
            'ContractValue' => $contract->contract_value,
            'ContractLength' => $contract->contract_length,
            'RiskScore' => $contract->risk_score,
        ];
    }
}
