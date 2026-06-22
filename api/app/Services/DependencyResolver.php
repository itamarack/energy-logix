<?php

namespace App\Services;

use App\Enums\FormulaVariable;
use App\Exceptions\CircularDependencyException;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\Token;
use Throwable;

class DependencyResolver
{
    public function __construct(
        private readonly Lexer $lexer
    ) {}

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
            $stream = $this->lexer->tokenize($expression);
            $tokens = [];
            
            while (!$stream->isEOF()) {
                if ($stream->current->type === Token::NAME_TYPE) {
                    $tokens[] = $stream->current->value;
                }
                $stream->next();
            }
        } catch (Throwable) {
            return [];
        }

        $baseVariables = FormulaVariable::values();

        return collect($tokens)
            ->map(fn(mixed $value): string => (string) $value)
            ->reject(fn(string $value) => in_array($value, $baseVariables, true))
            ->unique()
            ->values()
            ->all();
    }
}
