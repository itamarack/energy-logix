<?php

namespace App\Services;

use App\Exceptions\DivisionByZeroException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Throwable;

class FormulaEvaluator
{
    public function __construct(
        private readonly ExpressionLanguage $el
    ) {}

    public function evaluate(string $expression, array $variables): float
    {
        try {
            $result = $this->el->evaluate($expression, $variables);

            if (is_infinite($result) || is_nan($result)) {
                throw new DivisionByZeroException;
            }

            return (float) $result;
        } catch (SyntaxError $e) {
            if (str_contains($e->getMessage(), 'Variable')) {
                preg_match('/Variable "([^"]+)"/', $e->getMessage(), $matches);
                throw new UndefinedVariableException($matches[1]);
            }

            throw new ParseException($e->getMessage());
        } catch (Throwable $e) {
            if ($e instanceof \DivisionByZeroError || str_contains($e->getMessage(), 'Division by zero')) {
                throw new DivisionByZeroException;
            }

            throw new ParseException($e->getMessage());
        }
    }

    public function validate(string $expression, array $allowedVariables): void
    {
        try {
            $this->el->parse($expression, $allowedVariables);
        } catch (SyntaxError $e) {
            if (str_contains($e->getMessage(), 'Variable')) {
                preg_match('/Variable "([^"]+)"/', $e->getMessage(), $matches);
                throw new UndefinedVariableException($matches[1]);
            }

            throw new ParseException($e->getMessage());
        }
    }
}
