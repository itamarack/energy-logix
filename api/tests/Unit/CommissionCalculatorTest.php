<?php

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionCalculator;
use App\Services\DependencyResolver;
use App\Services\FormulaEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\FormulaVariableSeeder::class);
    \Illuminate\Support\Facades\Cache::flush();
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * Resolve a CommissionCalculator instance with real service dependencies.
 */
function makeCalculator(): CommissionCalculator
{
    $evaluator = new FormulaEvaluator(new \Symfony\Component\ExpressionLanguage\ExpressionLanguage());

    return new CommissionCalculator($evaluator, new DependencyResolver(new \Symfony\Component\ExpressionLanguage\Lexer()));
}

// -------------------------------------------------------------------------
// Simple formula — no intermediate variables
// -------------------------------------------------------------------------

it('evaluates a simple formula and returns the correct numeric result', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 10000.0,
        'contract_length' => 24,
    ]);

    // Expected: (10000 * 0.05) + (24 * 100) = 500 + 2400 = 2900
    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(2900.0);
});

// -------------------------------------------------------------------------
// Intermediate variables — dependency order and steps
// -------------------------------------------------------------------------

it('evaluates a formula with two intermediates and records correct calculation steps', function (): void {
    // base_commission = annual_usage * 0.05
    // AdjustedCommission = base_commission * 1.1
    // Result = AdjustedCommission + (contract_length * 50)
    $formula = FormulaVersion::factory()->create([
        'expression' => 'AdjustedCommission + (contract_length * 50)',
        'variables' => [
            ['name' => 'AdjustedCommission', 'expression' => 'base_commission * 1.1'],
            ['name' => 'base_commission', 'expression' => 'annual_usage * 0.05'],
        ],
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 20000.0,
        'contract_length' => 12,
    ]);

    // base_commission = 20000 * 0.05 = 1000
    // AdjustedCommission = 1000 * 1.1 = 1100
    // Result = 1100 + (12 * 50) = 1100 + 600 = 1700
    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(1700.0);

    $steps = $calculation->steps;

    // Intermediates first, then RESULT — three entries total
    expect($steps)->toHaveCount(3);

    // base_commission must be evaluated before AdjustedCommission
    $variables = array_column($steps, 'variable');
    $baseIdx = array_search('base_commission', $variables);
    $adjustedIdx = array_search('AdjustedCommission', $variables);
    $resultIdx = array_search('RESULT', $variables);

    expect($baseIdx)->toBeLessThan($adjustedIdx)
        ->and($adjustedIdx)->toBeLessThan($resultIdx);

    expect($steps[$resultIdx]['expression'])->toBe('AdjustedCommission + (contract_length * 50)');
    // JSON round-trip may return int/float; use toEqual for numeric comparison
    expect((float) $steps[$resultIdx]['value'])->toEqual(1700.0);
});

// -------------------------------------------------------------------------
// Zero value handling
// -------------------------------------------------------------------------

it('handles a contract with zero annual_usage and contract_length without error', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 0,
        'contract_length' => 0,
    ]);

    // (0 * 0.05) + (0 * 100) = 0
    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(0.0);
});
