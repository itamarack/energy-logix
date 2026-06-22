<?php

use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Services\DependencyResolver;
use App\Services\FormulaEvaluator;
use App\Services\FormulaValidator;

/**
 * @phpstan-type Variable array{name: string, expression: string}
 */
beforeEach(function (): void {
    $evaluator = new FormulaEvaluator;
    $this->validator = new FormulaValidator($evaluator, new DependencyResolver($evaluator));
});

// -------------------------------------------------------------------------
// Happy-path: valid formulas
// -------------------------------------------------------------------------

it('passes for a valid simple formula with no intermediate variables', function (): void {
    expect(fn () => $this->validator->validate('AnnualUsage * 0.05', []))->not->toThrow(Throwable::class);
});

it('passes for a valid formula referencing a single valid intermediate variable', function (): void {
    expect(fn () => $this->validator->validate('BaseCommission * 1.1', [
        ['name' => 'BaseCommission', 'expression' => 'AnnualUsage * 0.05'],
    ]))->not->toThrow(Throwable::class);
});

it('passes for a valid formula with multiple chained intermediate variables', function (): void {
    expect(fn () => $this->validator->validate('AdjustedCommission + 500', [
        ['name' => 'BaseCommission', 'expression' => 'AnnualUsage * 0.05'],
        ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * RiskScore'],
    ]))->not->toThrow(Throwable::class);
});

// -------------------------------------------------------------------------
// Syntax errors
// -------------------------------------------------------------------------

it('raises ParseException for a syntax error in the main expression', function (): void {
    expect(fn () => $this->validator->validate('AnnualUsage ** ContractValue', []))
        ->toThrow(ParseException::class);
});

it('raises ParseException for a syntax error in an intermediate variable expression', function (): void {
    expect(fn () => $this->validator->validate('BaseCommission * 1.1', [
        ['name' => 'BaseCommission', 'expression' => 'AnnualUsage ** 2'],
    ]))->toThrow(ParseException::class);
});

// -------------------------------------------------------------------------
// Undefined variable references
// -------------------------------------------------------------------------

it('raises UndefinedVariableException for an undefined variable in the main expression', function (): void {
    try {
        $this->validator->validate('AnnualUsage * PeakDemand', []);
        $this->fail('Expected UndefinedVariableException to be thrown');
    } catch (UndefinedVariableException $e) {
        expect($e->getMessage())->toContain('PeakDemand');
    }
});

it('raises UndefinedVariableException when an intermediate variable references an undeclared identifier', function (): void {
    // Ghost is not declared as an intermediate and is not a base input
    expect(fn () => $this->validator->validate('BaseCommission * 1.1', [
        ['name' => 'BaseCommission', 'expression' => 'Ghost * 0.05'],
    ]))->toThrow(UndefinedVariableException::class);
});

// -------------------------------------------------------------------------
// Circular dependency detection
// -------------------------------------------------------------------------

it('raises CircularDependencyException for a 2-node cycle between intermediate variables', function (): void {
    expect(fn () => $this->validator->validate('BaseCommission * 1.0', [
        ['name' => 'BaseCommission', 'expression' => 'AdjustedCommission * 1.1'],
        ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * 0.9'],
    ]))->toThrow(CircularDependencyException::class);
});

it('names cycle members in the CircularDependencyException message', function (): void {
    try {
        $this->validator->validate('BaseCommission * 1.0', [
            ['name' => 'BaseCommission', 'expression' => 'AdjustedCommission * 1.1'],
            ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * 0.9'],
        ]);

        $this->fail('Expected CircularDependencyException to be thrown');
    } catch (CircularDependencyException $e) {
        expect($e->getMessage())
            ->toContain('BaseCommission')
            ->toContain('AdjustedCommission');
    }
});
