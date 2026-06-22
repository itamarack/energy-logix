<?php

use App\Http\Controllers\CalculationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\FormulaVersionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/formula-versions', [FormulaVersionController::class, 'index'])->name('formula-versions.index');
Route::get('/formula-versions/create', [FormulaVersionController::class, 'create'])->name('formula-versions.create');
Route::post('/formula-versions', [FormulaVersionController::class, 'store'])->name('formula-versions.store');
Route::get('/formula-versions/{formulaVersion}', [FormulaVersionController::class, 'show'])->name('formula-versions.show');

Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');

Route::get('/calculations', [CalculationController::class, 'index'])->name('calculations.index');
Route::get('/calculations/{calculation}', [CalculationController::class, 'show'])->name('calculations.show');
