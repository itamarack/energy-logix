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
    $result = $this->evaluator->evaluate('AnnualUsage * ContractValue + 100', [
        'AnnualUsage' => 5000,
        'ContractValue' => 2.5,
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
    $result = $this->evaluator->evaluate('AnnualUsage + ContractLength + RiskScore', [
        'AnnualUsage' => 1000,
        'ContractLength' => 12,
        'RiskScore' => 0.5,
    ]);

    expect($result)->toBe(1012.5);
});

// ---------------------------------------------------------------------------
// ParseException
// ---------------------------------------------------------------------------

test('evaluate throws ParseException for exponentiation operator **', function () {
    $this->evaluator->evaluate('AnnualUsage ** ContractValue', [
        'AnnualUsage' => 5000,
        'ContractValue' => 2,
    ]);
})->throws(ParseException::class);

test('evaluate throws ParseException for unrecognised at-sign operator', function () {
    $this->evaluator->evaluate('AnnualUsage @ 5', ['AnnualUsage' => 100]);
})->throws(ParseException::class);

test('evaluate throws ParseException for unrecognised hash character prefix', function () {
    $this->evaluator->evaluate('#AnnualUsage', ['AnnualUsage' => 100]);
})->throws(ParseException::class);

// ---------------------------------------------------------------------------
// UndefinedVariableException
// ---------------------------------------------------------------------------

test('evaluate throws UndefinedVariableException for missing variable', function () {
    $this->evaluator->evaluate('AnnualUsage * PeakDemand', ['AnnualUsage' => 5000]);
})->throws(UndefinedVariableException::class, 'PeakDemand');

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
    $this->evaluator->evaluate('AnnualUsage / 0', ['AnnualUsage' => 5000]);
})->throws(DivisionByZeroException::class);

test('evaluate throws DivisionByZeroException when divisor resolves to zero', function () {
    $this->evaluator->evaluate('AnnualUsage / ZeroVar', [
        'AnnualUsage' => 5000,
        'ZeroVar' => 0,
    ]);
})->throws(DivisionByZeroException::class);

// ---------------------------------------------------------------------------
// validate()
// ---------------------------------------------------------------------------

test('validate passes for expression using only allowed variables', function () {
    expect(fn () => $this->evaluator->validate(
        'AnnualUsage * ContractValue',
        ['AnnualUsage', 'ContractValue']
    ))->not->toThrow(Throwable::class);
});

test('validate throws UndefinedVariableException for variable not in allowed list', function () {
    $this->evaluator->validate('AnnualUsage * Unknown', ['AnnualUsage']);
})->throws(UndefinedVariableException::class, 'Unknown');

test('validate throws ParseException for syntactically invalid expression', function () {
    $this->evaluator->validate('AnnualUsage ** 2', ['AnnualUsage']);
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
