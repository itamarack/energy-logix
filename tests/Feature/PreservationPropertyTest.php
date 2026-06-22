<?php

/**
 * Preservation Property Tests — Task 2
 *
 * These tests encode the EXPECTED preservation behaviours of the Dynamic
 * Commission Engine BEFORE the implementation exists.  They will fail with
 * table-not-found (or 404/500) errors until migrations, models, and the full
 * service layer are in place — that is intentional and expected.
 *
 * Once the implementation is complete every test here must pass.  They act as
 * the regression-guard for all "non-bug" paths.
 *
 * Validates: Requirements 1.2.2, 1.2.3, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3,
 *            4.1, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5
 *
 * **Validates: Requirements 1.2.2, 1.2.3, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3,
 *              4.1, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5**
 */

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

// ---------------------------------------------------------------------------
// Property 4-A: Correct commission calculation — result matches manual math
//
// Validates: Requirements 2.1, 2.2, 5.1, 5.2
// ---------------------------------------------------------------------------

test('correct commission is calculated for a known formula and seeded contract', function (): void {
    /** @var FormulaVersion $formula */
    $formula = FormulaVersion::factory()->create([
        'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 10000,
        'contract_length' => 24,
        'contract_value' => 50000,
        'risk_score' => 3.5,
    ]);

    // Manual expected: (10000 * 0.05) + (24 * 100) = 500 + 2400 = 2900
    $expectedResult = (10000 * 0.05) + (24 * 100);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertSuccessful();

    $body = $response->json();
    expect((float) $body['data']['result'])->toBe((float) $expectedResult);
});

// ---------------------------------------------------------------------------
// Property 4-B: Audit record created with all required fields
//
// Validates: Requirements 2.3, 2.4, 5.1, 5.2
// ---------------------------------------------------------------------------

test('commission calculation persists exactly one new record with all required fields', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 20000,
        'contract_length' => 12,
        'contract_value' => 100000,
        'risk_score' => 5.0,
    ]);

    $beforeCount = CommissionCalculation::count();

    $this->postJson("/api/v1/contracts/{$contract->id}/calculate")->assertSuccessful();

    // Exactly one new record
    expect(CommissionCalculation::count())->toBe($beforeCount + 1);

    $record = CommissionCalculation::latest('calculated_at')->first();

    expect($record)->not->toBeNull();
    expect($record->formula_version_id)->not->toBeNull();
    expect($record->contract_id)->toBe($contract->id);
    expect($record->input_values)->toBeArray()->not->toBeEmpty();
    expect($record->calculation_steps)->toBeArray()->not->toBeEmpty();
    expect($record->result)->toBeNumeric();
    expect($record->calculated_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Property 5-A: GET /api/v1/calculations returns records ordered newest first
//
// Validates: Requirements 5.5
// ---------------------------------------------------------------------------

test('GET /api/v1/calculations returns records ordered by calculated_at descending', function (): void {
    /** @var FormulaVersion $formula */
    $formula = FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.05',
        'variables' => [],
        'is_active' => true,
    ]);

    $contracts = Contract::factory()->count(3)->create();

    // Seed three calculations with deliberate timestamps going backwards
    CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[0]->id,
        'calculated_at' => now()->subHours(2),
    ]);

    CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[1]->id,
        'calculated_at' => now()->subHour(),
    ]);

    CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[2]->id,
        'calculated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/calculations');

    $response->assertSuccessful();

    $records = $response->json('data');

    expect(count($records))->toBe(3);

    // Verify descending order: each record's calculated_at is >= the next
    for ($i = 0; $i < count($records) - 1; $i++) {
        expect($records[$i]['calculated_at'])->toBeGreaterThanOrEqual($records[$i + 1]['calculated_at']);
    }
});

// ---------------------------------------------------------------------------
// Property 1.2.2 / 1.2.3: Activation deactivates all other versions
//
// Validates: Requirements 1.2.2, 1.2.3
// ---------------------------------------------------------------------------

test('activating formula version B deactivates version A and only one version is active', function (): void {
    /** @var FormulaVersion $versionA */
    $versionA = FormulaVersion::factory()->create(['is_active' => true]);

    /** @var FormulaVersion $versionB */
    $versionB = FormulaVersion::factory()->create(['is_active' => false]);

    $this->postJson("/api/v1/formula-versions/{$versionB->id}/activate")
        ->assertSuccessful();

    $versionA->refresh();
    $versionB->refresh();

    expect($versionA->is_active)->toBeFalse();
    expect($versionB->is_active)->toBeTrue();

    // Only one active version in the whole table
    expect(FormulaVersion::where('is_active', true)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Property 3.3: Simulation does not persist commission_calculations records
//
// Validates: Requirements 3.1, 3.3
// ---------------------------------------------------------------------------

test('simulation does not create commission_calculations records', function (): void {
    /** @var FormulaVersion $activeFormula */
    $activeFormula = FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.05',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var FormulaVersion $targetFormula */
    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.06',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::factory()->count(5)->create();

    $beforeCount = CommissionCalculation::count();

    $this->postJson("/api/v1/formula-versions/{$targetFormula->id}/simulate")
        ->assertSuccessful();

    expect(CommissionCalculation::count())->toBe($beforeCount);
});

// ---------------------------------------------------------------------------
// Property 4-C: Topological evaluation order — result is correct regardless
//               of variable declaration order in JSON
//
// Validates: Requirements 4.1, 4.4, 2.2
// ---------------------------------------------------------------------------

test('formula with intermediate variables evaluated in topological order produces correct result', function (): void {
    // BaseCommission = AnnualUsage * 0.05
    // AdjustedCommission = BaseCommission * 1.1
    // Final = AdjustedCommission + (ContractLength * 10)
    //
    // With AnnualUsage=10000, ContractLength=12:
    //   BaseCommission     = 10000 * 0.05       = 500
    //   AdjustedCommission = 500 * 1.1          = 550
    //   Final              = 550 + (12 * 10)    = 670

    $expectedResult = 670.0;

    // Declare variables in REVERSE dependency order to prove topological sort
    /** @var FormulaVersion $formula */
    $formula = FormulaVersion::factory()->create([
        'expression' => 'AdjustedCommission + (ContractLength * 10)',
        'variables' => [
            // AdjustedCommission declared BEFORE BaseCommission intentionally
            ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * 1.1'],
            ['name' => 'BaseCommission',      'expression' => 'AnnualUsage * 0.05'],
        ],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 10000,
        'contract_length' => 12,
        'contract_value' => 50000,
        'risk_score' => 2.0,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertSuccessful();

    expect((float) $response->json('data.result'))->toBe($expectedResult);
});

// ---------------------------------------------------------------------------
// Property 4-D: Calculation steps contain each intermediate plus RESULT entry
//
// Validates: Requirements 2.2, 2.4, 5.2
// ---------------------------------------------------------------------------

test('calculation_steps contains one entry per intermediate variable plus RESULT', function (): void {
    /** @var FormulaVersion $formula */
    $formula = FormulaVersion::factory()->create([
        'expression' => 'BaseCommission * 1.1',
        'variables' => [
            ['name' => 'BaseCommission', 'expression' => 'AnnualUsage * 0.05'],
        ],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 10000,
        'contract_length' => 12,
        'contract_value' => 50000,
        'risk_score' => 2.0,
    ]);

    $this->postJson("/api/v1/contracts/{$contract->id}/calculate")->assertSuccessful();

    $record = CommissionCalculation::latest('calculated_at')->first();

    $steps = $record->calculation_steps;
    $stepVars = array_column($steps, 'variable');

    expect($stepVars)->toContain('BaseCommission');
    expect($stepVars)->toContain('RESULT');

    // The RESULT step must be last
    expect(end($stepVars))->toBe('RESULT');
});

// ---------------------------------------------------------------------------
// Property 5-B: Simulation returns the correct aggregate shape
//
// Validates: Requirements 3.1, 3.2
// ---------------------------------------------------------------------------

test('simulation returns affected_contract_count, current_total_commission, new_total_commission, and difference', function (): void {
    /** @var FormulaVersion $activeFormula */
    FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.05',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var FormulaVersion $targetFormula */
    $targetFormula = FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.06',
        'variables' => [],
        'is_active' => false,
    ]);

    Contract::factory()->count(3)->create([
        'annual_usage' => 10000,
        'contract_length' => 12,
        'contract_value' => 50000,
        'risk_score' => 2.0,
    ]);

    $response = $this->postJson("/api/v1/formula-versions/{$targetFormula->id}/simulate");

    $response->assertSuccessful();

    $body = $response->json();

    expect($body)->toHaveKeys([
        'affected_contract_count',
        'current_total_commission',
        'new_total_commission',
        'difference',
    ]);

    expect((int) $body['affected_contract_count'])->toBe(3);

    // current: 3 * (10000 * 0.05) = 3 * 500 = 1500
    expect((float) $body['current_total_commission'])->toBe(1500.0);

    // new: 3 * (10000 * 0.06) = 3 * 600 = 1800
    expect((float) $body['new_total_commission'])->toBe(1800.0);

    expect((float) $body['difference'])->toBe(300.0);
});

// ---------------------------------------------------------------------------
// Property-based: For all contracts with non-negative fields and a simple
// formula with no intermediates, the API result equals PHP arithmetic.
//
// Validates: Requirements 2.1, 4.4
// ---------------------------------------------------------------------------

test('property-based: commission result matches PHP arithmetic for random non-negative contract fields', function (
    float $annualUsage,
    float $contractValue,
    int $contractLength,
    float $riskScore,
): void {
    // Fixed formula with no intermediates — simple linear combination
    FormulaVersion::factory()->create([
        'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => $annualUsage,
        'contract_length' => $contractLength,
        'contract_value' => $contractValue,
        'risk_score' => $riskScore,
    ]);

    // Reference computation in PHP — same arithmetic the evaluator must produce
    $expectedResult = ($annualUsage * 0.05) + ($contractLength * 100);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertSuccessful();

    expect((float) $response->json('data.result'))->toBe((float) $expectedResult);
})->with([
    // Representative sample covering a wide numeric range
    'small contract' => [1000.0, 5000.0, 12, 1.0],
    'medium contract' => [50000.0, 250000.0, 36, 5.0],
    'large contract' => [500000.0, 1000000.0, 60, 9.5],
    'zero annual usage' => [0.0, 10000.0, 24, 3.0],
    'zero contract length' => [10000.0, 50000.0, 0, 2.0],
    'all zeros' => [0.0, 0.0, 0, 0.0],
    'fractional values' => [12345.67, 98765.43, 18, 4.75],
    'very small values' => [1.0, 1.0, 1, 0.01],
    'large annual high rate' => [999999.99, 999999.99, 60, 10.0],
]);

// ---------------------------------------------------------------------------
// Property-based: Input values map stored in audit record uses PascalCase keys
//
// Validates: Requirements 2.3, 2.4, 5.2
// ---------------------------------------------------------------------------

test('property-based: input_values in audit record uses PascalCase keys matching contract data', function (
    float $annualUsage,
    float $contractValue,
    int $contractLength,
    float $riskScore,
): void {
    FormulaVersion::factory()->create([
        'expression' => 'AnnualUsage * 0.05',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => $annualUsage,
        'contract_length' => $contractLength,
        'contract_value' => $contractValue,
        'risk_score' => $riskScore,
    ]);

    $this->postJson("/api/v1/contracts/{$contract->id}/calculate")->assertSuccessful();

    $record = CommissionCalculation::latest('calculated_at')->first();

    $inputValues = $record->input_values;

    // Must use PascalCase keys
    expect($inputValues)->toHaveKeys([
        'AnnualUsage',
        'ContractValue',
        'ContractLength',
        'RiskScore',
    ]);

    // Values must match what was stored on the contract
    expect((float) $inputValues['AnnualUsage'])->toBe((float) $annualUsage);
    expect((float) $inputValues['ContractValue'])->toBe((float) $contractValue);
    expect((int) $inputValues['ContractLength'])->toBe($contractLength);
    expect((float) $inputValues['RiskScore'])->toBe((float) $riskScore);
})->with([
    'standard contract' => [10000.0, 50000.0, 24, 3.5],
    'zero values' => [0.0, 0.0, 0, 0.0],
    'large values' => [500000.0, 1000000.0, 60, 10.0],
]);
