<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommissionCalculationResource;
use App\Models\CommissionCalculation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CalculationController extends Controller
{
    public function index(Request $request): InertiaResponse|AnonymousResourceCollection
    {
        $calculations = CommissionCalculation::with(['formulaVersion', 'contract'])
            ->orderBy('calculated_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return CommissionCalculationResource::collection($calculations);
        }

        return Inertia::render('Calculations/Index', [
            'calculations' => CommissionCalculationResource::collection($calculations)->resolve(),
        ]);
    }

    public function show(Request $request, CommissionCalculation $calculation): InertiaResponse|CommissionCalculationResource
    {
        $calculation->load(['formulaVersion', 'contract']);

        if ($request->wantsJson()) {
            return new CommissionCalculationResource($calculation);
        }

        return Inertia::render('Calculations/Show', [
            'calculation' => (new CommissionCalculationResource($calculation))->resolve(),
        ]);
    }
}
