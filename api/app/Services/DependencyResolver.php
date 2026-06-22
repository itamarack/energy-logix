<?php

namespace App\Services;

use App\Enums\FormulaVariable;
use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;

class DependencyResolver
{
    public function __construct(private readonly FormulaEvaluator $evaluator) {}

    public function resolve(array $variables): array
    {
        $expressions = array_column($variables, 'expression', 'name');
        
        $visiting = [];
        $visited = [];
        $sorted = [];

        foreach (array_keys($expressions) as $name) {
            $this->visitNode($name, $expressions, $visiting, $visited, $sorted);
        }

        return $sorted;
    }

    private function visitNode(string $name, array $expressions, array &$visiting, array &$visited, array &$sorted): void 
    {
        if (isset($visiting[$name])) {
            throw new CircularDependencyException(array_keys($visiting));
        }

        if (isset($visited[$name])) {
            return;
        }

        $visiting[$name] = true;

        foreach ($this->extractIdentifiers($expressions[$name]) as $dependency) {
            if (isset($expressions[$dependency])) {
                $this->visitNode($dependency, $expressions, $visiting, $visited, $sorted);
            }
        }

        unset($visiting[$name]);
        $visited[$name] = true;
        $sorted[] = $name;
    }

    private function extractIdentifiers(string $expression): array
    {
        try {
            $tokens = $this->evaluator->tokenise($expression);
        } catch (ParseException) {
            return [];
        }

        $baseVariables = FormulaVariable::values();

        return collect($tokens)
            ->where('type', FormulaEvaluator::IDENT)
            ->pluck('value')
            ->map(fn(mixed $value): string => (string) $value)
            ->reject(fn(string $value) => in_array($value, $baseVariables, true))
            ->unique()
            ->values()
            ->all();
    }
}
