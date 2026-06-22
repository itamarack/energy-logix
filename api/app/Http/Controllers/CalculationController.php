<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommissionCalculationResource;
use App\Models\CommissionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CalculationController extends Controller
{
    public function index(): JsonResponse
    {
        $calculations = CommissionCalculation::with(['formulaVersion', 'contract'])
            ->orderBy('calculated_at', 'desc')
            ->get();

        return response()->json(
            CommissionCalculationResource::collection($calculations),
            Response::HTTP_OK
        );
    }

    public function show(CommissionCalculation $calculation): JsonResponse
    {
        $calculation->load(['formulaVersion', 'contract']);

        return response()->json(
            new CommissionCalculationResource($calculation),
            Response::HTTP_OK
        );
    }
}
