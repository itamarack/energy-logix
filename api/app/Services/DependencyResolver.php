<?php

namespace App\Services;

use App\Exceptions\CircularDependencyException;

class DependencyResolver
{
    /** @var array<int, string> */
    private const BASE_INPUT_VARIABLES = ['AnnualUsage', 'ContractValue', 'ContractLength', 'RiskScore'];

    public function __construct(private readonly FormulaEvaluator $evaluator) {}

    public function resolve(array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        $expressions = array_column($variables, 'expression', 'name');
        $sorted = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $name) use (&$visit, &$sorted, &$visiting, &$visited, $expressions): void {
            if (isset($visiting[$name])) {
                throw new CircularDependencyException(array_keys($visiting));
            }

            if (isset($visited[$name])) {
                return;
            }

            $visiting[$name] = true;

            foreach ($this->extractIdentifiers($expressions[$name]) as $dep) {
                if (isset($expressions[$dep])) {
                    $visit($dep);
                }
            }

            unset($visiting[$name]);
            $visited[$name] = true;
            $sorted[] = $name;
        };

        foreach (array_keys($expressions) as $name) {
            $visit($name);
        }

        return $sorted;
    }

    /**
     * @return array<int, string>
     */
    private function extractIdentifiers(string $expression): array
    {
        try {
            $tokens = $this->evaluator->tokenise($expression);
        } catch (\Throwable) {
            return [];
        }

        $identifiers = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'IDENT' && ! in_array($token['value'], self::BASE_INPUT_VARIABLES, true)) {
                $identifiers[] = (string) $token['value'];
            }
        }

        return array_values(array_unique($identifiers));
    }
}
