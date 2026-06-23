<?php

namespace App\Services;

use App\Models\FormulaVariable;
use Illuminate\Support\Facades\Cache;

class FormulaValidator
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function validate(string $expression, array $variables): void
    {
        $baseVariables = Cache::remember('formula_variables', 3600, function () {
            return FormulaVariable::pluck('name')->toArray();
        });

        $intermediateNames = array_map(fn (array $var): string => $var['name'], $variables);
        $allowedForIntermediates = array_merge($baseVariables, $intermediateNames);

        foreach ($variables as $variable) {
            $this->evaluator->validate($variable['expression'], $allowedForIntermediates);
        }

        $this->resolver->resolve($variables);

        $allowedForMain = array_merge($baseVariables, $intermediateNames);
        $this->evaluator->validate($expression, $allowedForMain);
    }
}
