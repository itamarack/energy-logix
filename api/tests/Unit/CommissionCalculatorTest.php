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

    $steps = $calculation->calculation_steps;

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
// Audit record persistence
// -------------------------------------------------------------------------

it('persists a CommissionCalculation audit record with all required fields', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 5000.0,
        'contract_value' => 50000.0,
        'contract_length' => 36,
        'risk_score' => 3.5,
    ]);

    $countBefore = CommissionCalculation::count();

    $calculation = makeCalculator()->calculate($formula, $contract);

    // One new record created
    expect(CommissionCalculation::count())->toBe($countBefore + 1);

    // Correct foreign keys
    expect($calculation->formula_version_id)->toBe($formula->id)
        ->and($calculation->contract_id)->toBe($contract->id);

    // input_values uses PascalCase keys
    $inputValues = $calculation->input_values;
    expect($inputValues)->toHaveKey('annual_usage')
        ->and($inputValues)->toHaveKey('contract_value')
        ->and($inputValues)->toHaveKey('contract_length')
        ->and($inputValues)->toHaveKey('risk_score');

    expect($inputValues['annual_usage'])->toEqual(5000.0)
        ->and($inputValues['contract_value'])->toEqual(50000.0)
        ->and($inputValues['contract_length'])->toEqual(36)
        ->and($inputValues['risk_score'])->toEqual(3.5);

    // calculation_steps ends with RESULT entry
    $steps = $calculation->calculation_steps;
    expect($steps)->not->toBeEmpty();
    $lastStep = end($steps);
    expect($lastStep['variable'])->toBe('RESULT');

    // Result is a float
    expect($calculation->result)->toBeFloat();

    // calculated_at is set
    expect($calculation->calculated_at)->not->toBeNull();
});

// -------------------------------------------------------------------------
// Zero-value contract fields
// -------------------------------------------------------------------------

it('handles a contract with zero annual_usage and contract_length without crashing', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 0.0,
        'contract_length' => 0,
        'contract_value' => 0.0,
        'risk_score' => 0.0,
    ]);

    // (0 * 0.05) + (0 * 100) = 0
    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(0.0);
    expect(CommissionCalculation::count())->toBe(1);
});
