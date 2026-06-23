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
            ->paginate(10);

        return CommissionCalculationResource::collection($calculations)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show(CommissionCalculation $calculation): JsonResponse
    {
        $calculation->load(['formulaVersion', 'contract']);

        return (new CommissionCalculationResource($calculation))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
