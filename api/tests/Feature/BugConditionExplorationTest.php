<?php

/**
 * Bug Condition Exploration Test — Task 1
 *
 * These tests encode the EXPECTED behaviour of the Dynamic Commission Engine.
 * On unfixed / unimplemented code every test here MUST FAIL — that failure is
 * the success signal for this task, proving the bugs exist.
 *
 * Do NOT attempt to fix these tests or the application code when they fail.
 * They become the regression-guard once the implementation is complete.
 *
 * Validates: Requirements 1.1.3, 1.1.4, 1.1.5, 1.2.4, 2.5, 3.3, 4.2, 4.3
 */

/**
 * Test Case A — isBugCondition: containsCircularDependency = true
 *
 * POST /api/v1/formula-versions with variables that form a cycle:
 *   BaseCommission    = AdjustedCommission * 1.1
 *   AdjustedCommission = BaseCommission * 0.9
 *
 * Expected: 422 with both cycle-member names present in the response body.
 * On unfixed code the system has no DependencyResolver running at save time,
 * so it silently accepts the formula and returns 201 — proving the bug.
 */
test('A: circular dependency formula is rejected with 422 and cycle members named', function (): void {
    $payload = [
        'name' => 'Circular Commission Formula',
        'expression' => 'BaseCommission + 0',
        'variables' => [
            ['name' => 'BaseCommission',     'expression' => 'AdjustedCommission * 1.1'],
            ['name' => 'AdjustedCommission', 'expression' => 'BaseCommission * 0.9'],
        ],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    // Must be rejected
    $response->assertStatus(422);

    // Both cycle members must appear in the error body
    $body = $response->getContent();
    expect($body)
        ->toContain('BaseCommission')
        ->toContain('AdjustedCommission');
});

/**
 * Test Case B — isBugCondition: referencesUndefinedVariable = true
 *
 * POST /api/v1/formula-versions with expression `AnnualUsage * PeakDemand`
 * where `PeakDemand` is not a base input variable and is not declared as an
 * intermediate variable.
 *
 * Expected: 422 with the unknown variable name in the response body.
 * On unfixed code the undefined reference is silently zero-ed, returning 201.
 */
test('B: formula referencing undefined variable is rejected with 422 and variable name in response', function (): void {
    $payload = [
        'name' => 'Undefined Variable Formula',
        'expression' => 'AnnualUsage * PeakDemand',
        'variables' => [],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertStatus(422);

    $body = $response->getContent();
    expect($body)->toContain('PeakDemand');
});

/**
 * Test Case C — isBugCondition: isSyntacticallyInvalid = true
 *
 * POST /api/v1/formula-versions with expression `AnnualUsage ** ContractValue`
 * (double-star `**` is not part of the supported grammar: +, -, *, /, parens).
 *
 * Expected: 422.
 * On unfixed code (or with eval()-based evaluation) the expression may be
 * accepted or evaluated as PHP exponentiation, returning 201.
 */
test('C: syntactically invalid expression is rejected with 422', function (): void {
    $payload = [
        'name' => 'Invalid Syntax Formula',
        'expression' => 'AnnualUsage ** ContractValue',
        'variables' => [],
    ];

    $response = $this->postJson('/api/v1/formula-versions', $payload);

    $response->assertStatus(422);
});

/**
 * Test Case D — isBugCondition: noActiveFormulaExists() = true
 *
 * POST /api/v1/contracts/{id}/calculate when no formula_version has
 * is_active = true.
 *
 * Expected:
 *   - 422 response
 *   - commission_calculations row count unchanged (no record created)
 *
 * On unfixed code (no active-formula guard in controller) the system may
 * return null / 500, or silently create a broken record.
 *
 * Note: because no migrations exist yet, the DB tables do not exist.  The
 * test will fail with a 404 or 500 — either way it is NOT a 422, confirming
 * the bug.
 */
test('D: calculate returns 422 and creates no record when no active formula exists', function (): void {
    // There is no active formula in a fresh database
    $beforeCount = DB::table('commission_calculations')->count();

    // Use contract ID 1; the endpoint must guard against missing active formula
    // before even looking up the contract
    $response = $this->postJson('/api/v1/contracts/1/calculate');

    $response->assertStatus(422);

    $afterCount = DB::table('commission_calculations')->count();
    expect($afterCount)->toBe($beforeCount);
});

/**
 * Test Case E — isBugCondition: simulationPersistsRecords = true
 *
 * POST /api/v1/formula-versions/{id}/simulate should run a dry-run across all
 * contracts WITHOUT creating any commission_calculations rows.
 *
 * Expected: commission_calculations count is identical before and after.
 *
 * On unfixed code (CommissionSimulator calls CommissionCalculator directly)
 * each contract generates a persisted record — count increases after the call.
 *
 * Note: because no migrations exist yet the test will fail with a 404 or 500,
 * confirming the bug.
 */
test('E: simulation does not persist commission_calculations records', function (): void {
    $beforeCount = DB::table('commission_calculations')->count();

    // Simulate against formula version ID 1 (may not exist yet — 404/500 proves
    // the unimplemented state, still not a "count unchanged + 200" success)
    $response = $this->postJson('/api/v1/formula-versions/1/simulate');

    // Must NOT be a 2xx success that also persists records
    // Either the route doesn't exist (404/500) or the simulation persists rows (bug)
    $afterCount = DB::table('commission_calculations')->count();

    // On correctly implemented code both of these must hold simultaneously:
    $response->assertSuccessful();
    expect($afterCount)->toBe($beforeCount);
});
