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

        $variableNames = array_map(fn (array $var): string => $var['name'], $variables);
        $variableSet = array_flip($variableNames);

        /** @var array<string, array<int, string>> $adjacency */
        $adjacency = array_fill_keys($variableNames, []);

        /** @var array<string, int> $inDegree */
        $inDegree = array_fill_keys($variableNames, 0);

        foreach ($variables as $variable) {
            $referencedIdents = $this->extractIdentifiers($variable['expression']);

            foreach ($referencedIdents as $ident) {
                if (isset($variableSet[$ident]) && $ident !== $variable['name']) {
                    $adjacency[$ident][] = $variable['name'];
                    $inDegree[$variable['name']]++;
                }
            }
        }

        $queue = [];
        foreach ($inDegree as $name => $degree) {
            if ($degree === 0) {
                $queue[] = $name;
            }
        }

        $sorted = [];
        while ($queue !== []) {
            $current = array_shift($queue);
            $sorted[] = $current;

            foreach ($adjacency[$current] as $dependent) {
                $inDegree[$dependent]--;
                if ($inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        if (count($sorted) < count($variables)) {
            $cycleMembers = array_values(array_diff($variableNames, $sorted));
            throw new CircularDependencyException($cycleMembers);
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
