<?php

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

test('GET /api/v1/contracts returns 200 with all contracts', function (): void {
    Contract::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/contracts');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(5);

    expect($data[0])->toHaveKeys([
        'id', 'name', 'annual_usage', 'contract_value',
        'contract_length', 'risk_score', 'created_at',
    ]);
});

test('POST /api/v1/contracts/{id}/calculate with an active formula returns 200 and persists one new record', function (): void {
    FormulaVersion::factory()->create([
        'expression' => '(annual_usage * 0.05) + (contract_length * 100)',
        'variables' => [],
        'is_active' => true,
    ]);

    $contract = Contract::factory()->create([
        'annual_usage' => 10000.0,
        'contract_length' => 24,
        'contract_value' => 50000.0,
        'risk_score' => 3.5,
    ]);

    $beforeCount = CommissionCalculation::count();

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id', 'formula_version_id', 'contract_id',
            'input_values', 'calculation_steps', 'result', 'calculated_at',
        ],
    ]);

    expect(CommissionCalculation::count())->toBe($beforeCount + 1);
});

test('POST /api/v1/contracts/{id}/calculate with no active formula returns 422 and creates no record', function (): void {

    FormulaVersion::factory()->create(['is_active' => false]);

    $contract = Contract::factory()->create();

    $beforeCount = CommissionCalculation::count();

    $response = $this->postJson("/api/v1/contracts/{$contract->id}/calculate");

    $response->assertUnprocessable();
    $response->assertJsonPath('message', 'No active formula version exists');

    expect(CommissionCalculation::count())->toBe($beforeCount);
});
