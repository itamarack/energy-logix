<?php

namespace App\Services;

use App\Exceptions\DivisionByZeroException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;

class FormulaEvaluator
{
    private const NUMBER = 'NUMBER';

    private const IDENT = 'IDENT';

    private const PLUS = 'PLUS';

    private const MINUS = 'MINUS';

    private const STAR = 'STAR';

    private const SLASH = 'SLASH';

    private const LPAREN = 'LPAREN';

    private const RPAREN = 'RPAREN';

    private const NODE_NUMBER = 'NODE_NUMBER';

    private const NODE_IDENT = 'NODE_IDENT';

    private const NODE_BINARY_OP = 'NODE_BINARY_OP';

    /** @var array<int, array{type: string, value: string|float|null}> */
    private array $tokens = [];

    private int $position = 0;

    /**
     * @param  array<string, float|int>  $variables
     *
     * @throws ParseException
     * @throws UndefinedVariableException
     * @throws DivisionByZeroException
     */
    public function evaluate(string $expression, array $variables): float
    {
        $this->tokens = $this->tokenise($expression);
        $this->position = 0;

        $ast = $this->parseExpression();

        if ($this->position < count($this->tokens)) {
            $unexpected = $this->tokens[$this->position]['value'] ?? 'unknown';
            throw new ParseException("Unexpected token '{$unexpected}' at position {$this->position}");
        }

        return $this->evaluateNode($ast, $variables);
    }

    public function validate(string $expression, array $allowedVariables): void
    {
        $tokens = $this->tokenise($expression);

        foreach ($tokens as $token) {
            if ($token['type'] === self::IDENT && ! in_array($token['value'], $allowedVariables, true)) {
                throw new UndefinedVariableException((string) $token['value']);
            }
        }

        $this->tokens = $tokens;
        $this->position = 0;
        $this->parseExpression();

        if ($this->position < count($this->tokens)) {
            $unexpected = $this->tokens[$this->position]['value'] ?? 'unknown';
            throw new ParseException("Unexpected token '{$unexpected}' at position {$this->position}");
        }
    }

    public function tokenise(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;

                continue;
            }

            if (ctype_digit($char) || ($char === '.' && $i + 1 < $length && ctype_digit($expression[$i + 1]))) {
                $start = $i;
                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $i++;
                }
                $tokens[] = ['type' => self::NUMBER, 'value' => (float) substr($expression, $start, $i - $start)];

                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }
                $tokens[] = ['type' => self::IDENT, 'value' => substr($expression, $start, $i - $start)];

                continue;
            }

            switch ($char) {
                case '+':
                    $tokens[] = ['type' => self::PLUS, 'value' => '+'];
                    $i++;

                    continue 2;
                case '-':
                    $tokens[] = ['type' => self::MINUS, 'value' => '-'];
                    $i++;

                    continue 2;
                case '*':
                    $tokens[] = ['type' => self::STAR, 'value' => '*'];
                    $i++;

                    continue 2;
                case '/':
                    $tokens[] = ['type' => self::SLASH, 'value' => '/'];
                    $i++;

                    continue 2;
                case '(':
                    $tokens[] = ['type' => self::LPAREN, 'value' => '('];
                    $i++;

                    continue 2;
                case ')':
                    $tokens[] = ['type' => self::RPAREN, 'value' => ')'];
                    $i++;

                    continue 2;
                default:
                    throw new ParseException("Unrecognised character '{$char}' at position {$i}");
            }
        }

        return $tokens;
    }

    private function parseExpression(): array
    {
        $node = $this->parseTerm();

        while ($this->position < count($this->tokens)
            && in_array($this->tokens[$this->position]['type'], [self::PLUS, self::MINUS], true)) {
            $operator = (string) $this->tokens[$this->position]['value'];
            $this->position++;
            $right = $this->parseTerm();
            $node = [
                'type' => self::NODE_BINARY_OP,
                'operator' => $operator,
                'left' => $node,
                'right' => $right,
            ];
        }

        return $node;
    }

    private function parseTerm(): array
    {
        $node = $this->parseFactor();

        while ($this->position < count($this->tokens)
            && in_array($this->tokens[$this->position]['type'], [self::STAR, self::SLASH], true)) {
            $operator = (string) $this->tokens[$this->position]['value'];
            $this->position++;
            $right = $this->parseFactor();
            $node = [
                'type' => self::NODE_BINARY_OP,
                'operator' => $operator,
                'left' => $node,
                'right' => $right,
            ];
        }

        return $node;
    }

    private function parseFactor(): array
    {
        if ($this->position >= count($this->tokens)) {
            throw new ParseException('Unexpected end of expression: expected a number, variable, or opening parenthesis');
        }

        $token = $this->tokens[$this->position];

        if ($token['type'] === self::NUMBER) {
            $this->position++;

            return ['type' => self::NODE_NUMBER, 'value' => (float) $token['value']];
        }

        if ($token['type'] === self::IDENT) {
            $this->position++;

            return ['type' => self::NODE_IDENT, 'name' => (string) $token['value']];
        }

        if ($token['type'] === self::LPAREN) {
            $this->position++;
            $node = $this->parseExpression();

            if ($this->position >= count($this->tokens) || $this->tokens[$this->position]['type'] !== self::RPAREN) {
                throw new ParseException('Expected closing parenthesis');
            }

            $this->position++;

            return $node;
        }

        throw new ParseException("Unexpected token '{$token['value']}': expected a number, variable, or opening parenthesis");
    }

    private function evaluateNode(array $node, array $variables): float
    {
        return match ($node['type']) {
            self::NODE_NUMBER => (float) $node['value'],

            self::NODE_IDENT => isset($variables[$node['name']])
                ? (float) $variables[$node['name']]
                : throw new UndefinedVariableException((string) $node['name']),

            self::NODE_BINARY_OP => $this->evaluateBinaryOp(
                (string) $node['operator'],
                $this->evaluateNode($node['left'], $variables),
                $this->evaluateNode($node['right'], $variables),
            ),

            default => throw new ParseException("Unknown AST node type: {$node['type']}"),
        };
    }

    private function evaluateBinaryOp(string $operator, float $left, float $right): float
    {
        return match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right == 0 ? throw new DivisionByZeroException : $left / $right,
            default => throw new ParseException("Unknown operator: {$operator}"),
        };
    }
}
