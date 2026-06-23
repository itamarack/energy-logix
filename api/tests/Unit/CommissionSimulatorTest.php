<?php

use App\DTOs\SimulationResult;
use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionSimulator;
use App\Services\DependencyResolver;
use App\Services\FormulaEvaluator;
use App\Services\FormulaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\FormulaVariableSeeder::class);
    \Illuminate\Support\Facades\Cache::flush();
});

function makeSimulator(): CommissionSimulator
{
    $evaluator = new FormulaEvaluator(new \Symfony\Component\ExpressionLanguage\ExpressionLanguage());
    $resolver = new DependencyResolver(new \Symfony\Component\ExpressionLanguage\Lexer());
    $validator = new FormulaValidator($evaluator, $resolver);

    return new CommissionSimulator($validator, $evaluator, $resolver);
}

function expectedCommission(Contract $contract): float
{
    return ($contract->annual_usage * 0.05) + ($contract->contract_length * 100);
}

it('returns affected_contract_count equal to the number of contracts in the database', function (): void {
    $targetFormula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::factory()->count(5)->create();

    $result = makeSimulator()->simulate($targetFormula);

    expect($result)->toBeInstanceOf(SimulationResult::class);
    expect($result->affected_contract_count)->toBe(5);
});

it('returns totals that match independently computed sums for both formulas', function (): void {

    $activeFormula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'annual_usage * 0.08',
        'variables' => [],
        'is_active' => false,
    ]);

    $contracts = Contract::factory()->count(3)->create([
        'annual_usage' => 10000.0,
        'contract_length' => 12,
        'contract_value' => 50000.0,
        'risk_score' => 5.0,
    ]);

    $expectedCurrentTotal = 0.0;
    $expectedNewTotal = 0.0;

    foreach ($contracts as $contract) {
        $expectedCurrentTotal += ($contract->annual_usage * 0.05) + ($contract->contract_length * 100);
        $expectedNewTotal += $contract->annual_usage * 0.08;
    }

    $result = makeSimulator()->simulate($targetFormula);

    expect($result->current_total_commission)->toEqual($expectedCurrentTotal);
    expect($result->new_total_commission)->toEqual($expectedNewTotal);
});

it('returns difference equal to new_total_commission minus current_total_commission', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'annual_usage * 0.1',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::factory()->count(4)->create([
        'annual_usage' => 8000.0,
        'contract_length' => 24,
        'contract_value' => 40000.0,
        'risk_score' => 3.0,
    ]);

    $result = makeSimulator()->simulate($targetFormula);

    expect($result->difference)->toEqual($result->new_total_commission - $result->current_total_commission);
});

it('does not create any commission_calculations records during simulation', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'annual_usage * 0.07',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::factory()->count(5)->create();

    $countBefore = CommissionCalculation::count();

    makeSimulator()->simulate($targetFormula);

    expect(CommissionCalculation::count())->toBe($countBefore);
});

it('returns zeroes for all totals when no contracts exist', function (): void {
    $targetFormula = FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::truncate();

    $result = makeSimulator()->simulate($targetFormula);

    expect($result->affected_contract_count)->toBe(0);
    expect($result->current_total_commission)->toBe(0.0);
    expect($result->new_total_commission)->toBe(0.0);
    expect($result->difference)->toBe(0.0);
});

it('returns 0 for current_total_commission when no active formula exists', function (): void {

    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'annual_usage * 0.05',
        'variables' => [],
        'is_active' => false,
    ]);

    $contracts = Contract::factory()->count(3)->create([
        'annual_usage' => 5000.0,
        'contract_length' => 12,
        'contract_value' => 30000.0,
        'risk_score' => 2.0,
    ]);

    $result = makeSimulator()->simulate($targetFormula);

    expect($result->current_total_commission)->toBe(0.0);

    $expectedNew = array_sum($contracts->map(fn ($c) => $c->annual_usage * 0.05)->toArray());
    expect($result->new_total_commission)->toEqual($expectedNew);
});

it('handles intermediate variables correctly during simulation without persisting records', function (): void {

    FormulaVersion::factory()->create([
        'expression' => 'annual_usage * 0.05',
        'variables' => [],
        'is_active' => true,
    ]);

    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'base_commission + (contract_length * 50)',
        'variables' => [
            ['name' => 'base_commission', 'expression' => 'annual_usage * 0.05'],
        ],
        'is_active' => false,
    ]);

    $contracts = Contract::factory()->count(2)->create([
        'annual_usage' => 10000.0,
        'contract_length' => 12,
        'contract_value' => 50000.0,
        'risk_score' => 5.0,
    ]);

    $countBefore = CommissionCalculation::count();

    $result = makeSimulator()->simulate($targetFormula);

    expect(CommissionCalculation::count())->toBe($countBefore);

    $expectedNew = 2 * 1100.0;
    expect($result->new_total_commission)->toEqual($expectedNew);
    expect($result->affected_contract_count)->toBe(2);
});
