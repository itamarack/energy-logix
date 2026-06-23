<?php

use App\Models\FormulaVersion;
use Database\Seeders\FormulaVariableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->beforeEach(function () {
    $this->seed(FormulaVariableSeeder::class);
    Cache::flush();
})->in('Feature');

pest()
    ->beforeEach(function (): void {
        FormulaVersion::factory()->create([
            'name' => 'Bug Exploration Seed',
            'expression' => 'annual_usage * 0.05',
            'variables' => [],
            'is_active' => false,
        ]);
    })
    ->in('Feature/BugConditionExplorationTest.php');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something() {}
