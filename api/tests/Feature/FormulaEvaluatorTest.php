<?php

use App\Exceptions\DivisionByZeroException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Services\FormulaEvaluator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

function getEvaluator(): FormulaEvaluator
{
    return new FormulaEvaluator(new ExpressionLanguage);
}

test('evaluate returns correct result for simple multiplication and addition', function () {
    $result = getEvaluator()->evaluate('annual_usage * contract_value + 100', [
        'annual_usage' => 5000,
        'contract_value' => 2.5,
    ]);

    expect($result)->toBe(12600.0);
});

test('evaluate respects operator precedence (multiplication before addition)', function () {
    $result = getEvaluator()->evaluate('2 + 3 * 4', []);

    expect($result)->toBe(14.0);
});

test('evaluate handles parentheses to override precedence', function () {
    $result = getEvaluator()->evaluate('(2 + 3) * 4', []);

    expect($result)->toBe(20.0);
});

test('evaluate handles subtraction and division', function () {
    $result = getEvaluator()->evaluate('10 - 4 / 2', []);

    expect($result)->toBe(8.0);
});

test('evaluate resolves multiple variables', function () {
    $result = getEvaluator()->evaluate('annual_usage + contract_length + risk_score', [
        'annual_usage' => 1000,
        'contract_length' => 12,
        'risk_score' => 0.5,
    ]);

    expect($result)->toBe(1012.5);
});

test('evaluate handles exponentiation operator **', function () {
    $result = getEvaluator()->evaluate('annual_usage ** contract_value', [
        'annual_usage' => 5000,
        'contract_value' => 2,
    ]);

    expect($result)->toBe(25000000.0);
});

test('evaluate throws ParseException for unrecognised at-sign operator', function () {
    getEvaluator()->evaluate('annual_usage @ 5', ['annual_usage' => 100]);
})->throws(ParseException::class);

test('evaluate throws ParseException for unrecognised hash character prefix', function () {
    getEvaluator()->evaluate('#annual_usage', ['annual_usage' => 100]);
})->throws(ParseException::class);

test('evaluate throws UndefinedVariableException for missing variable', function () {
    getEvaluator()->evaluate('annual_usage * peak_demand', ['annual_usage' => 5000]);
})->throws(UndefinedVariableException::class, 'peak_demand');

test('UndefinedVariableException message includes the variable name', function () {
    try {
        getEvaluator()->evaluate('X * Y', ['X' => 1]);
    } catch (UndefinedVariableException $e) {
        expect($e->getMessage())->toContain('Y');
    }
});

test('evaluate throws DivisionByZeroException when dividing by zero literal', function () {
    getEvaluator()->evaluate('annual_usage / 0', ['annual_usage' => 5000]);
})->throws(DivisionByZeroException::class);

test('evaluate throws DivisionByZeroException when divisor resolves to zero', function () {
    getEvaluator()->evaluate('annual_usage / zero_var', [
        'annual_usage' => 5000,
        'zero_var' => 0,
    ]);
})->throws(DivisionByZeroException::class);

test('validate passes for expression using only allowed variables', function () {
    expect(fn () => getEvaluator()->validate(
        'annual_usage * contract_value',
        ['annual_usage', 'contract_value']
    ))->not->toThrow(Throwable::class);
});

test('validate throws UndefinedVariableException for variable not in allowed list', function () {
    getEvaluator()->validate('annual_usage * unknown', ['annual_usage']);
})->throws(UndefinedVariableException::class, 'unknown');

test('validate throws ParseException for syntactically invalid expression', function () {
    getEvaluator()->validate('annual_usage @ 2', ['annual_usage']);
})->throws(ParseException::class);
