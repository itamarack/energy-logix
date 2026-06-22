<?php

namespace App\Services;

use App\Enums\FormulaNode;
use App\Enums\FormulaToken;
use App\Exceptions\DivisionByZeroException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;

class FormulaEvaluator
{
    /**
     * @param  array<string, float|int>  $variables
     *
     * @throws ParseException
     * @throws UndefinedVariableException
     * @throws DivisionByZeroException
     */
    public function evaluate(string $expression, array $variables): float
    {
        $tokens = $this->tokenise($expression);
        $position = 0;

        $ast = $this->parseExpression($tokens, $position);

        if ($position < count($tokens)) {
            $unexpected = $tokens[$position]['value'] ?? 'unknown';
            throw new ParseException("Unexpected token '{$unexpected}' at position {$position}");
        }

        return $this->evaluateNode($ast, $variables);
    }

    public function validate(string $expression, array $allowedVariables): void
    {
        $tokens = $this->tokenise($expression);

        foreach ($tokens as $token) {
            if ($token['type'] === FormulaToken::IDENT && ! in_array($token['value'], $allowedVariables, true)) {
                throw new UndefinedVariableException((string) $token['value']);
            }
        }

        $position = 0;
        $this->parseExpression($tokens, $position);

        if ($position < count($tokens)) {
            $unexpected = $tokens[$position]['value'] ?? 'unknown';
            throw new ParseException("Unexpected token '{$unexpected}' at position {$position}");
        }
    }

    /**
     * @return array<int, array{type: FormulaToken, value: string|float|null}>
     *
     * @throws ParseException
     */
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
                $tokens[] = ['type' => FormulaToken::NUMBER, 'value' => (float) substr($expression, $start, $i - $start)];

                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }
                $tokens[] = ['type' => FormulaToken::IDENT, 'value' => substr($expression, $start, $i - $start)];

                continue;
            }

            switch ($char) {
                case '+':
                    $tokens[] = ['type' => FormulaToken::PLUS, 'value' => '+'];
                    $i++;

                    continue 2;
                case '-':
                    $tokens[] = ['type' => FormulaToken::MINUS, 'value' => '-'];
                    $i++;

                    continue 2;
                case '*':
                    $tokens[] = ['type' => FormulaToken::STAR, 'value' => '*'];
                    $i++;

                    continue 2;
                case '/':
                    $tokens[] = ['type' => FormulaToken::SLASH, 'value' => '/'];
                    $i++;

                    continue 2;
                case '(':
                    $tokens[] = ['type' => FormulaToken::LPAREN, 'value' => '('];
                    $i++;

                    continue 2;
                case ')':
                    $tokens[] = ['type' => FormulaToken::RPAREN, 'value' => ')'];
                    $i++;

                    continue 2;
                default:
                    throw new ParseException("Unrecognised character '{$char}' at position {$i}");
            }
        }

        return $tokens;
    }

    /**
     * @param array<int, array{type: FormulaToken, value: string|float|null}> $tokens
     * @return array<string, mixed>
     */
    private function parseExpression(array $tokens, int &$position): array
    {
        $node = $this->parseTerm($tokens, $position);

        while ($position < count($tokens)
            && in_array($tokens[$position]['type'], [FormulaToken::PLUS, FormulaToken::MINUS], true)) {
            $operator = (string) $tokens[$position]['value'];
            $position++;
            $right = $this->parseTerm($tokens, $position);
            $node = [
                'type' => FormulaNode::BINARY_OP,
                'operator' => $operator,
                'left' => $node,
                'right' => $right,
            ];
        }

        return $node;
    }

    /**
     * @param array<int, array{type: FormulaToken, value: string|float|null}> $tokens
     * @return array<string, mixed>
     */
    private function parseTerm(array $tokens, int &$position): array
    {
        $node = $this->parseFactor($tokens, $position);

        while ($position < count($tokens)
            && in_array($tokens[$position]['type'], [FormulaToken::STAR, FormulaToken::SLASH], true)) {
            $operator = (string) $tokens[$position]['value'];
            $position++;
            $right = $this->parseFactor($tokens, $position);
            $node = [
                'type' => FormulaNode::BINARY_OP,
                'operator' => $operator,
                'left' => $node,
                'right' => $right,
            ];
        }

        return $node;
    }

    /**
     * @param array<int, array{type: FormulaToken, value: string|float|null}> $tokens
     * @return array<string, mixed>
     */
    private function parseFactor(array $tokens, int &$position): array
    {
        if ($position >= count($tokens)) {
            throw new ParseException('Unexpected end of expression: expected a number, variable, or opening parenthesis');
        }

        $token = $tokens[$position];

        if ($token['type'] === FormulaToken::NUMBER) {
            $position++;

            return ['type' => FormulaNode::NUMBER, 'value' => (float) $token['value']];
        }

        if ($token['type'] === FormulaToken::IDENT) {
            $position++;

            return ['type' => FormulaNode::IDENT, 'name' => (string) $token['value']];
        }

        if ($token['type'] === FormulaToken::LPAREN) {
            $position++;
            $node = $this->parseExpression($tokens, $position);

            if ($position >= count($tokens) || $tokens[$position]['type'] !== FormulaToken::RPAREN) {
                throw new ParseException('Expected closing parenthesis');
            }

            $position++;

            return $node;
        }

        throw new ParseException("Unexpected token '{$token['value']}': expected a number, variable, or opening parenthesis");
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, float|int>  $variables
     *
     * @throws UndefinedVariableException
     * @throws DivisionByZeroException
     */
    private function evaluateNode(array $node, array $variables): float
    {
        return match ($node['type']) {
            FormulaNode::NUMBER => (float) $node['value'],

            FormulaNode::IDENT => isset($variables[$node['name']])
                ? (float) $variables[$node['name']]
                : throw new UndefinedVariableException((string) $node['name']),

            FormulaNode::BINARY_OP => $this->evaluateBinaryOp(
                (string) $node['operator'],
                $this->evaluateNode($node['left'], $variables),
                $this->evaluateNode($node['right'], $variables),
            ),

            default => throw new ParseException("Unknown AST node type: {$node['type']->name}"),
        };
    }

    /**
     * @throws DivisionByZeroException
     */
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
