<?php

use App\Events\FormulaVersionDeactivated;
use App\Jobs\GenerateClosingReport;
use App\Models\CommissionCalculation;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

it('dispatches FormulaVersionDeactivated event when a formula is deactivated', function () {
    /** @var TestCase $this */
    Event::fake([FormulaVersionDeactivated::class]);

    $formula = FormulaVersion::factory()->create(['is_active' => true]);

    $this->postJson("/api/v1/formula-versions/{$formula->id}/deactivate")
        ->assertStatus(200);

    Event::assertDispatched(FormulaVersionDeactivated::class, function ($event) use ($formula) {
        return $event->formulaVersion->id === $formula->id;
    });
});

it('queues GenerateClosingReport job when FormulaVersionDeactivated is fired if calculations exist', function () {
    Queue::fake();
    Storage::fake('local');

    $formula = FormulaVersion::factory()->create(['is_active' => true]);
    CommissionCalculation::factory()->create(['formula_version_id' => $formula->id]);

    event(new FormulaVersionDeactivated($formula));

    Queue::assertPushed(GenerateClosingReport::class, function ($job) use ($formula) {
        return $job->formulaVersion->id === $formula->id;
    });
});

it('does NOT queue GenerateClosingReport job if no calculations exist', function () {
    Queue::fake();

    $formula = FormulaVersion::factory()->create(['is_active' => true]);

    event(new FormulaVersionDeactivated($formula));

    Queue::assertNotPushed(GenerateClosingReport::class);
});

it('does NOT queue GenerateClosingReport job if the report already exists', function () {
    Queue::fake();
    Storage::fake('local');

    $formula = FormulaVersion::factory()->create(['is_active' => true]);
    CommissionCalculation::factory()->create(['formula_version_id' => $formula->id]);

    Storage::disk('local')->put("reports/formula_{$formula->id}_closing_report.csv", 'dummy content');

    event(new FormulaVersionDeactivated($formula));

    Queue::assertNotPushed(GenerateClosingReport::class);
});

it('generates a CSV file with commission calculations when the job runs', function () {
    Storage::fake('local');

    $formula = FormulaVersion::factory()->create(['is_active' => true]);
    $contract = Contract::factory()->create();

    CommissionCalculation::factory()->create([
        'formula_version_id' => $formula->id,
        'contract_id' => $contract->id,
        'result' => 1234.56,
        'calculated_at' => now(),
    ]);

    $job = new GenerateClosingReport($formula);
    $job->handle();

    $filename = "reports/formula_{$formula->id}_closing_report.csv";
    expect(Storage::disk('local')->exists($filename))->toBeTrue();

    $content = Storage::disk('local')->get($filename);
    expect($content)
        ->toContain('"Calculation ID","Contract ID","Calculated At",Result')
        ->toContain('1234.56');
});

it('returns 404 when downloading a non-existent report', function () {
    /** @var TestCase $this */
    Storage::fake('local');

    $formula = FormulaVersion::factory()->create();

    $this->getJson("/api/v1/formula-versions/{$formula->id}/report")
        ->assertStatus(404)
        ->assertJson(['message' => 'Report not found or still generating']);
});

it('downloads the generated report', function () {
    /** @var TestCase $this */
    Storage::fake('local');

    $formula = FormulaVersion::factory()->create();
    $filename = "reports/formula_{$formula->id}_closing_report.csv";

    Storage::disk('local')->put($filename, '"Calculation ID","Contract ID","Calculated At",Result\n1,1,2026-06-23T12:00:00Z,500.00');

    $response = $this->get("/api/v1/formula-versions/{$formula->id}/report");

    $response->assertStatus(200);
    $response->assertDownload("formula_{$formula->id}_closing_report.csv");
});
