<?php

namespace App\Services;

use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;

class FormulaValidator
{
    /** @var array<int, string> */
    public const BASE_INPUT_VARIABLES = ['AnnualUsage', 'ContractValue', 'ContractLength', 'RiskScore'];

    /** @var array<string, string> */
    public const VARIABLE_COLUMN_MAP = [
        'AnnualUsage' => 'annual_usage',
        'ContractValue' => 'contract_value',
        'ContractLength' => 'contract_length',
        'RiskScore' => 'risk_score',
    ];

    public function __construct(
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    /**
     * @param  array<int, array{name: string, expression: string}>  $variables
     *
     * @throws ParseException
     * @throws UndefinedVariableException
     * @throws CircularDependencyException
     */
    public function validate(string $expression, array $variables): void
    {
        $intermediateNames = array_map(fn (array $var): string => $var['name'], $variables);
        $allowedForIntermediates = array_merge(self::BASE_INPUT_VARIABLES, $intermediateNames);

        foreach ($variables as $variable) {
            $this->evaluator->validate($variable['expression'], $allowedForIntermediates);
        }

        $this->resolver->resolve($variables);

        $allowedForMain = array_merge(self::BASE_INPUT_VARIABLES, $intermediateNames);
        $this->evaluator->validate($expression, $allowedForMain);
    }
}
