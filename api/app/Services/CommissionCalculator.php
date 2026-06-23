<?php

namespace App\Services;

use App\DTOs\CommissionCalculationData;

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

class CommissionCalculator
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function calculate(FormulaVersion $formula, Contract $contract): CommissionCalculationData
    {
        $baseVariables = \Illuminate\Support\Facades\Cache::remember('formula_variables', 3600, function () {
            return \App\Models\FormulaVariable::pluck('name')->toArray();
        });
        
        $inputValues = $contract->only($baseVariables);        
        $variableMap = $inputValues;

        $orderedNames = $this->resolver->resolve($formula->variables);
        $variablesByName = collect($formula->variables)->pluck('expression', 'name')->toArray();

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
            'variable' => CommissionCalculation::RESULT,
            'expression' => $formula->expression,
            'value' => $result,
        ];

        return new CommissionCalculationData(
            inputValues: $inputValues,
            steps: $steps,
            result: $result,
        );
    }
}
