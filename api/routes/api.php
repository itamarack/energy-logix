<?php

use App\Http\Controllers\CalculationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\FormulaVersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Formula Versions
    Route::get('/formula-versions', [FormulaVersionController::class, 'index'])
        ->name('api.formula-versions.index');

    Route::post('/formula-versions', [FormulaVersionController::class, 'store'])
        ->name('api.formula-versions.store');

    Route::get('/formula-versions/{formulaVersion}', [FormulaVersionController::class, 'show'])
        ->name('api.formula-versions.show');

    Route::post('/formula-versions/{formulaVersion}/activate', [FormulaVersionController::class, 'activate'])
        ->name('api.formula-versions.activate');

    Route::post('/formula-versions/{formulaVersion}/simulate', [FormulaVersionController::class, 'simulate'])
        ->name('api.formula-versions.simulate');

    // Contracts
    Route::get('/contracts', [ContractController::class, 'index'])
        ->name('api.contracts.index');

    Route::post('/contracts/{contract}/calculate', [ContractController::class, 'calculate'])
        ->name('api.contracts.calculate');

    // Calculations (audit trail)
    Route::get('/calculations', [CalculationController::class, 'index'])
        ->name('api.calculations.index');

    Route::get('/calculations/{calculation}', [CalculationController::class, 'show'])
        ->name('api.calculations.show');
});
