<?php

use App\Http\Controllers\CalculationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\FormulaVersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Formula versions
    Route::get('formula-versions', [FormulaVersionController::class, 'index']);
    Route::post('formula-versions', [FormulaVersionController::class, 'store']);
    Route::get('formula-versions/{formulaVersion}', [FormulaVersionController::class, 'show']);
    Route::post('formula-versions/{formulaVersion}/activate', [FormulaVersionController::class, 'activate']);
    Route::post('formula-versions/{formulaVersion}/simulate', [FormulaVersionController::class, 'simulate']);

    // Contracts
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts/{contract}/calculate', [ContractController::class, 'calculate']);

    // Calculations
    Route::get('calculations', [CalculationController::class, 'index']);
    Route::get('calculations/{calculation}', [CalculationController::class, 'show']);
});
