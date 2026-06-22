<?php

/**
 * Feature tests for FormulaVersionController — Task 12
 *
 * Validates: Requirements 1.1.3, 1.1.4, 1.1.5, 1.1.6, 1.2.2, 1.2.3, 3.1, 3.3, 8.3
 */

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('GET /api/v1/formula-versions returns 200 with a JSON array of resources', function (): void {
    FormulaVersion::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/formula-versions');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(3);

    // Each resource has the expected shape
    expect($data[0])->toHaveKeys([
        'id', 'name', 'description', 'version_number',
        'expression', 'variables', 'is_active', 'created_at',
    ]);
});

// ---------------------------------------------------------------------------
// store — valid formula
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions with a valid formula persists the record and returns 201', function (): void {
    $beforeCount = FormulaVersion::count();

    $payload = [
        'name' => 'Standard Energy Commission',
        'expression' => '(AnnualUsage * 0.05) + (ContractLength * 100)',
        'variables' => [],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.name', 'Standard Energy Commission');
    $response->assertJsonPath('data.expression', '(AnnualUsage * 0.05) + (ContractLength * 100)');
    $response->assertJsonPath('data.is_active', false);

    expect(FormulaVersion::count())->toBe($beforeCount + 1);
});

// ---------------------------------------------------------------------------
// store — circular dependency
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions with a circular dependency returns 422 with cycle member names', function (): void {
    $payload = [
        'name' => 'Circular Formula',
        'expression' => 'BaseCommission + 0',
        'variables' => [
            ['name' => 'BaseCommission',     'expression' => 'AdjustedCommission * 1.1'],
            ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * 0.9'],
        ],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertUnprocessable();

    $body = $response->getContent();
    expect($body)->toContain('BaseCommission')
        ->and($body)->toContain('AdjustedCommission');
});

// ---------------------------------------------------------------------------
// store — syntax error
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions with a syntax error returns 422', function (): void {
    $payload = [
        'name' => 'Bad Syntax Formula',
        'expression' => 'AnnualUsage ** ContractValue',
        'variables' => [],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// store — undefined variable
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions referencing an undefined variable returns 422 with variable name', function (): void {
    $payload = [
        'name' => 'Undefined Var Formula',
        'expression' => 'AnnualUsage * PeakDemand',
        'variables' => [],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertUnprocessable();
    expect($response->getContent())->toContain('PeakDemand');
});

// ---------------------------------------------------------------------------
// activate — deactivates others, only one active
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions/{id}/activate activates target and deactivates all others', function (): void {
    /** @var FormulaVersion $versionA */
    $versionA = FormulaVersion::factory()->create(['is_active' => true]);
    /** @var FormulaVersion $versionB */
    $versionB = FormulaVersion::factory()->create(['is_active' => false]);
    /** @var FormulaVersion $versionC */
    $versionC = FormulaVersion::factory()->create(['is_active' => false]);

    $response = $this->postJson("/api/v1/formula-versions/{$versionB->id}/activate");

    $response->assertOk();
    $response->assertJsonPath('data.id', $versionB->id);
    $response->assertJsonPath('data.is_active', true);

    // Refresh from DB
    $versionA->refresh();
    $versionB->refresh();
    $versionC->refresh();

    expect($versionA->is_active)->toBeFalse();
    expect($versionB->is_active)->toBeTrue();
    expect($versionC->is_active)->toBeFalse();

    // Exactly one active row in the table
    expect(FormulaVersion::where('is_active', true)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// simulate — returns all four keys, commission_calculations count unchanged
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions/{id}/simulate returns simulation result and does not persist records', function (): void {
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

    Contract::factory()->count(3)->create();

    $beforeCount = CommissionCalculation::count();

    $response = $this->postJson("/api/v1/formula-versions/{$targetFormula->id}/simulate");

    $response->assertOk();
    $response->assertJsonStructure([
        'affected_contract_count',
        'current_total_commission',
        'new_total_commission',
        'difference',
    ]);

    // Simulation must not have persisted any records
    expect(CommissionCalculation::count())->toBe($beforeCount);
});
