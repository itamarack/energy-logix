<?php

namespace App\Services;

use App\DTOs\CommissionCalculationData;
use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVariable;
use App\Models\FormulaVersion;
use Illuminate\Support\Facades\Cache;

class CommissionCalculator
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function calculate(FormulaVersion $formula, Contract $contract): CommissionCalculationData
    {
        $baseVariables = Cache::remember('formula_variables', 3600, function () {
            return FormulaVariable::pluck('name')->toArray();
        });

        $inputValues = $contract->only($baseVariables);
        $variableMap = $inputValues;

        $orderedNames = $this->resolver->resolve((array) $formula->variables);
        $variablesByName = collect((array) $formula->variables)->pluck('expression', 'name')->toArray();

        $steps = [];
        $stepNumber = 1;
        foreach ($orderedNames as $name) {
            $expression = $variablesByName[$name];
            $value = $this->evaluator->evaluate($expression, $variableMap);

            $steps[] = [
                'step' => $stepNumber++,
                'variable' => $name,
                'expression' => $expression,
                'value' => $value,
            ];

            $variableMap[$name] = $value;
        }

        $result = $this->evaluator->evaluate($formula->expression, $variableMap);

        $steps[] = [
            'step' => null,
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
