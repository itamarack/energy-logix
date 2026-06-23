<?php

use App\Models\FormulaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->beforeEach(function () {
    $this->seed(\Database\Seeders\FormulaVariableSeeder::class);
    \Illuminate\Support\Facades\Cache::flush();
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

function something()
{

}
