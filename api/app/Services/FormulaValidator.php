<?php

namespace App\Services;

use App\Enums\FormulaVariable;

class FormulaValidator
{
    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function validate(string $expression, array $variables): void
    {
        $intermediateNames = array_map(fn (array $var): string => $var['name'], $variables);
        $allowedForIntermediates = array_merge(FormulaVariable::values(), $intermediateNames);

        foreach ($variables as $variable) {
            $this->evaluator->validate($variable['expression'], $allowedForIntermediates);
        }

        $this->resolver->resolve($variables);

        $allowedForMain = array_merge(FormulaVariable::values(), $intermediateNames);
        $this->evaluator->validate($expression, $allowedForMain);
    }
}
