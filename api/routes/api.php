<?php

use App\Http\Controllers\CalculationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\FormulaVariableController;
use App\Http\Controllers\FormulaVersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function (): void {
    Route::prefix('formula-variables')->group(function () {
        Route::get('/', [FormulaVariableController::class, 'index'])->name('formula-variables.index');
    });

    Route::prefix('formula-versions')->group(function () {
        Route::get('/', [FormulaVersionController::class, 'index'])->name('formula-versions.index');
        Route::post('/', [FormulaVersionController::class, 'store'])->name('formula-versions.store');
        Route::put('/{formulaVersion}', [FormulaVersionController::class, 'update'])->name('formula-versions.update');
        Route::get('/{formulaVersion}', [FormulaVersionController::class, 'show'])->name('formula-versions.show');

        Route::post('/{formulaVersion}/activate', [FormulaVersionController::class, 'activate'])->name('formula-versions.activate');
        Route::post('/{formulaVersion}/deactivate', [FormulaVersionController::class, 'deactivate'])->name('formula-versions.deactivate');
        Route::post('/{formulaVersion}/simulate', [FormulaVersionController::class, 'simulate'])->name('formula-versions.simulate');
    });

    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('/{contract}', [ContractController::class, 'show'])->name('contracts.show');
        Route::get('/{contract}/calculations', [ContractController::class, 'calculations'])->name('contracts.calculations');
        Route::post('/{contract}/calculate', [ContractController::class, 'calculate'])->name('contracts.calculate');
    });

    Route::prefix('calculations')->group(function () {
        Route::get('/', [CalculationController::class, 'index'])->name('calculations.index');
        Route::get('/{calculation}', [CalculationController::class, 'show'])->name('calculations.show');
    });

});
