<?php

use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;

test('GET /api/v1/calculations returns calculations ordered by calculated_at descending', function (): void

{
    /** @var \Tests\TestCase $this */

    $formula = FormulaVersion::factory()->create();
    $contracts = Contract::factory()->count(3)->create();

    $oldest = CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[0]->id,
        'calculated_at' => now()->subHours(3),
    ]);

    $middle = CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[1]->id,
        'calculated_at' => now()->subHour(),
    ]);

    $newest = CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contracts[2]->id,
        'calculated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/calculations');
    $response->assertOk();

    $records = $response->json('data');
    expect($records)->toHaveCount(3);

    expect($records[0]['id'])->toBe($newest->id);
    expect($records[1]['id'])->toBe($middle->id);
    expect($records[2]['id'])->toBe($oldest->id);

    for ($i = 0; $i < count($records) - 1; $i++) {
        expect($records[$i]['calculated_at'])->toBeGreaterThanOrEqual($records[$i + 1]['calculated_at']);
    }
});

test('GET /api/v1/calculations/{id} returns the full calculation record', function (): void

{
    /** @var \Tests\TestCase $this */
    $formula = FormulaVersion::factory()->create();
    $contract = Contract::factory()->create();

    $calculation = CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contract->id,
        'calculated_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/calculations/{$calculation->id}");
    $response->assertOk();

    $data = $response->json('data');

    expect($data['id'])->toBe($calculation->id);
    expect($data['formula_version_id'])->toBe($formula->id);
    expect($data['contract_id'])->toBe($contract->id);
    expect($data['input_values'])->toBeArray()->not->toBeEmpty();
    expect($data['calculation_steps'])->toBeArray()->not->toBeEmpty();
    expect($data['result'])->toBeNumeric();
    expect($data['calculated_at'])->not->toBeNull();
});
