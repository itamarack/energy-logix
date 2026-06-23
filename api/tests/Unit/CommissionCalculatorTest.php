<?php

use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionCalculator;
use App\Services\DependencyResolver;
use App\Services\FormulaEvaluator;
use Database\Seeders\FormulaVariableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Lexer;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    $this->seed(FormulaVariableSeeder::class);
    Cache::flush();
});

function makeCalculator(): CommissionCalculator
{
    $evaluator = new FormulaEvaluator(new ExpressionLanguage);

    return new CommissionCalculator($evaluator, new DependencyResolver(new Lexer));
}

it('evaluates a simple formula and returns the correct numeric result', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 10000.0,
        'contract_length' => 24,
    ]);

    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(2900.0);
});

it('evaluates a formula with two intermediates and records correct calculation steps', function (): void {

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

    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(1700.0);

    $steps = $calculation->steps;

    expect($steps)->toHaveCount(3);

    $variables = array_column($steps, 'variable');
    $baseIdx = array_search('base_commission', $variables);
    $adjustedIdx = array_search('AdjustedCommission', $variables);
    $resultIdx = array_search('RESULT', $variables);

    expect($baseIdx)->toBeLessThan($adjustedIdx)
        ->and($adjustedIdx)->toBeLessThan($resultIdx);

    expect($steps[$resultIdx]['expression'])->toBe('AdjustedCommission + (contract_length * 50)');

    expect((float) $steps[$resultIdx]['value'])->toEqual(1700.0);
});

it('handles a contract with zero annual_usage and contract_length without error', function (): void {
    $formula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 0,
        'contract_length' => 0,
    ]);

    $calculation = makeCalculator()->calculate($formula, $contract);

    expect($calculation->result)->toBe(0.0);
});
