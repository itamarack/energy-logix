<?php

use App\Exceptions\CircularDependencyException;
use App\Services\DependencyResolver;
use App\Services\FormulaEvaluator;
use Symfony\Component\ExpressionLanguage\Lexer;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\FormulaVariableSeeder::class);
    \Illuminate\Support\Facades\Cache::flush();
    $this->resolver = new DependencyResolver(new Lexer());
});

it('returns empty array for empty variables list', function (): void {
    expect($this->resolver->resolve([]))->toBe([]);
});

it('returns single variable with no intermediate dependencies', function (): void {
    $result = $this->resolver->resolve([
        ['name' => 'A', 'expression' => 'annual_usage * 2'],
    ]);

    expect($result)->toBe(['A']);
});

it('resolves two-level chain where B depends on A', function (): void {
    $result = $this->resolver->resolve([
        ['name' => 'B', 'expression' => 'A * 1.1'],
        ['name' => 'A', 'expression' => 'annual_usage * 2'],
    ]);

    expect($result)->toBe(['A', 'B']);
});

it('resolves three-variable chain A → B → C', function (): void {
    $result = $this->resolver->resolve([
        ['name' => 'C', 'expression' => 'B + 100'],
        ['name' => 'A', 'expression' => 'annual_usage * 0.05'],
        ['name' => 'B', 'expression' => 'A * contract_length'],
    ]);

    expect($result)->toBe(['A', 'B', 'C']);
});

it('resolves declaration order independence — same graph, different declaration orders', function (): void {
    $forward = $this->resolver->resolve([
        ['name' => 'X', 'expression' => 'annual_usage * 0.1'],
        ['name' => 'Y', 'expression' => 'X + 50'],
    ]);

    $reversed = $this->resolver->resolve([
        ['name' => 'Y', 'expression' => 'X + 50'],
        ['name' => 'X', 'expression' => 'annual_usage * 0.1'],
    ]);

    expect($forward)->toBe(['X', 'Y'])
        ->and($reversed)->toBe(['X', 'Y']);
});

it('resolves two independent variables (no edges between them)', function (): void {
    $result = $this->resolver->resolve([
        ['name' => 'Alpha', 'expression' => 'annual_usage * 2'],
        ['name' => 'Beta', 'expression' => 'contract_value * 0.1'],
    ]);

    expect($result)->toHaveCount(2)
        ->and($result)->toContain('Alpha')
        ->and($result)->toContain('Beta');
});

it('raises CircularDependencyException for a 2-node cycle', function (): void {
    expect(fn () => $this->resolver->resolve([
        ['name' => 'base_commission', 'expression' => 'AdjustedCommission * 1.1'],
        ['name' => 'AdjustedCommission', 'expression' => 'base_commission * 0.9'],
    ]))
        ->toThrow(CircularDependencyException::class);
});

it('names both cycle members in the CircularDependencyException message for a 2-node cycle', function (): void {
    try {
        $this->resolver->resolve([
            ['name' => 'base_commission', 'expression' => 'AdjustedCommission * 1.1'],
            ['name' => 'AdjustedCommission', 'expression' => 'base_commission * 0.9'],
        ]);

        $this->fail('Expected CircularDependencyException to be thrown');
    } catch (CircularDependencyException $e) {
        expect($e->getMessage())
            ->toContain('base_commission')
            ->toContain('AdjustedCommission');
    }
});

it('raises CircularDependencyException for a 3-node cycle A → B → C → A', function (): void {
    expect(fn () => $this->resolver->resolve([
        ['name' => 'A', 'expression' => 'B * 1'],
        ['name' => 'B', 'expression' => 'C * 1'],
        ['name' => 'C', 'expression' => 'A * 1'],
    ]))
        ->toThrow(CircularDependencyException::class);
});

it('names all three cycle members in message for a 3-node cycle', function (): void {
    try {
        $this->resolver->resolve([
            ['name' => 'A', 'expression' => 'B * 1'],
            ['name' => 'B', 'expression' => 'C * 1'],
            ['name' => 'C', 'expression' => 'A * 1'],
        ]);

        $this->fail('Expected CircularDependencyException to be thrown');
    } catch (CircularDependencyException $e) {
        expect($e->getMessage())
            ->toContain('A')
            ->toContain('B')
            ->toContain('C');
    }
});

it('does not create edges for base input variables', function (): void {

    $result = $this->resolver->resolve([
        ['name' => 'Fee', 'expression' => 'annual_usage * risk_score'],
        ['name' => 'Base', 'expression' => 'contract_value * contract_length'],
    ]);

    expect($result)->toHaveCount(2);
});
