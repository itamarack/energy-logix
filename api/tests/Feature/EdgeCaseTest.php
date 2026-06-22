<?php

/**
 * Edge case feature tests — Task 15
 *
 * Validates: Requirements 8.4
 */

use App\Models\Contract;
use App\Models\FormulaVersion;

// ---------------------------------------------------------------------------
// Empty expression string
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions with empty expression string returns 422', function (): void {
    $response = $this->postJson('/api/v1/formula-versions', [
        'name' => 'Empty Expression',
        'expression' => '',
        'variables' => [],
    ]);

    $response->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// snake_case variable name (not in allowed PascalCase base inputs)
// ---------------------------------------------------------------------------

test('POST /api/v1/formula-versions with snake_case variable in expression returns 422', function (): void {
    $response = $this->postJson('/api/v1/formula-versions', [
        'name' => 'Snake Case Formula',
        'expression' => 'annual_usage * 0.05',
        'variables' => [],
    ]);

    $response->assertUnprocessable();
    expect($response->getContent())->toContain('annual_usage');
});

// ---------------------------------------------------------------------------
// Negative annual_usage — calculation completes without exception
// ---------------------------------------------------------------------------

test('calculate with negative annual_usage completes without exception and returns valid float', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => -500.0,
        'contract_length' => 12,
        'contract_value' => 10000.0,
        'risk_score' => 2.0,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertOk();

    $result = $response->json('data.result');
    expect($result)->toBeNumeric();
});

// ---------------------------------------------------------------------------
// Zero contract_value — calculation completes without exception
// ---------------------------------------------------------------------------

test('calculate with zero contract_value completes without exception and returns valid float', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 10000.0,
        'contract_length' => 24,
        'contract_value' => 0.0,
        'risk_score' => 3.5,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertOk();

    $result = $response->json('data.result');
    expect($result)->toBeNumeric();
    expect((float) $result)->toBe((10000.0 * 0.05) + (24 * 100));
});

// ---------------------------------------------------------------------------
// Very large contract_value — no overflow
// ---------------------------------------------------------------------------

test('calculate with very large contract_value completes without overflow exception', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    /** @var Contract $contract */
    $contract = Contract::factory()->create([
        'annual_usage' => 500000.0,
        'contract_length' => 60,
        'contract_value' => 9_999_999.99,
        'risk_score' => 10.0,
    ]);

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertOk();

    $result = $response->json('data.result');
    expect($result)->toBeNumeric();
    expect((float) $result)->toBe((500000.0 * 0.05) + (60 * 100));
});
