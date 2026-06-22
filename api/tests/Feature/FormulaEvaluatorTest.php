<?php

use App\Exceptions\DivisionByZeroException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Services\FormulaEvaluator;

beforeEach(function () {
    $this->evaluator = new FormulaEvaluator;
});

// ---------------------------------------------------------------------------
// evaluate() — arithmetic correctness
// ---------------------------------------------------------------------------

test('evaluate returns correct result for simple multiplication and addition', function () {
    $result = $this->evaluator->evaluate('annual_usage * contract_value + 100', [
        'annual_usage' => 5000,
        'contract_value' => 2.5,
    ]);

    expect($result)->toBe(12600.0);
});

test('evaluate respects operator precedence (multiplication before addition)', function () {
    $result = $this->evaluator->evaluate('2 + 3 * 4', []);

    expect($result)->toBe(14.0);
});

test('evaluate handles parentheses to override precedence', function () {
    $result = $this->evaluator->evaluate('(2 + 3) * 4', []);

    expect($result)->toBe(20.0);
});

test('evaluate handles subtraction and division', function () {
    $result = $this->evaluator->evaluate('10 - 4 / 2', []);

    expect($result)->toBe(8.0);
});

test('evaluate resolves multiple variables', function () {
    $result = $this->evaluator->evaluate('annual_usage + contract_length + risk_score', [
        'annual_usage' => 1000,
        'contract_length' => 12,
        'risk_score' => 0.5,
    ]);

    expect($result)->toBe(1012.5);
});

// ---------------------------------------------------------------------------
// ParseException
// ---------------------------------------------------------------------------

test('evaluate throws ParseException for exponentiation operator **', function () {
    $this->evaluator->evaluate('annual_usage ** contract_value', [
        'annual_usage' => 5000,
        'contract_value' => 2,
    ]);
})->throws(ParseException::class);

test('evaluate throws ParseException for unrecognised at-sign operator', function () {
    $this->evaluator->evaluate('annual_usage @ 5', ['annual_usage' => 100]);
})->throws(ParseException::class);

test('evaluate throws ParseException for unrecognised hash character prefix', function () {
    $this->evaluator->evaluate('#annual_usage', ['annual_usage' => 100]);
})->throws(ParseException::class);

// ---------------------------------------------------------------------------
// UndefinedVariableException
// ---------------------------------------------------------------------------

test('evaluate throws UndefinedVariableException for missing variable', function () {
    $this->evaluator->evaluate('annual_usage * peak_demand', ['annual_usage' => 5000]);
})->throws(UndefinedVariableException::class, 'peak_demand');

test('UndefinedVariableException message includes the variable name', function () {
    try {
        $this->evaluator->evaluate('X * Y', ['X' => 1]);
    } catch (UndefinedVariableException $e) {
        expect($e->getMessage())->toContain('Y');
    }
});

// ---------------------------------------------------------------------------
// DivisionByZeroException
// ---------------------------------------------------------------------------

test('evaluate throws DivisionByZeroException when dividing by zero literal', function () {
    $this->evaluator->evaluate('annual_usage / 0', ['annual_usage' => 5000]);
})->throws(DivisionByZeroException::class);

test('evaluate throws DivisionByZeroException when divisor resolves to zero', function () {
    $this->evaluator->evaluate('annual_usage / zero_var', [
        'annual_usage' => 5000,
        'zero_var' => 0,
    ]);
})->throws(DivisionByZeroException::class);

// ---------------------------------------------------------------------------
// validate()
// ---------------------------------------------------------------------------

test('validate passes for expression using only allowed variables', function () {
    expect(fn () => $this->evaluator->validate(
        'annual_usage * contract_value',
        ['annual_usage', 'contract_value']
    ))->not->toThrow(Throwable::class);
});

test('validate throws UndefinedVariableException for variable not in allowed list', function () {
    $this->evaluator->validate('annual_usage * unknown', ['annual_usage']);
})->throws(UndefinedVariableException::class, 'unknown');

test('validate throws ParseException for syntactically invalid expression', function () {
    $this->evaluator->validate('annual_usage ** 2', ['annual_usage']);
})->throws(ParseException::class);

// ---------------------------------------------------------------------------
// tokenise()
// ---------------------------------------------------------------------------

test('tokenise produces correct token types', function () {
    $tokens = $this->evaluator->tokenise('A + 1.5 * (B - 2)');

    $types = array_column($tokens, 'type');

    expect($types)->toEqual([
        'IDENT', 'PLUS', 'NUMBER', 'STAR', 'LPAREN', 'IDENT', 'MINUS', 'NUMBER', 'RPAREN',
    ]);
});
