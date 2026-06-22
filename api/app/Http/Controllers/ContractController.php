<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommissionCalculationResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ContractController extends Controller
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            ContractResource::collection(Contract::all()),
            Response::HTTP_OK
        );
    }

    public function calculate(Contract $contract): JsonResponse
    {
        $activeFormula = FormulaVersion::query()->where('is_active', true)->first();

        if ($activeFormula === null) {
            return response()->json(
                ['message' => 'No active formula version exists'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $calculation = $this->calculator->calculate($activeFormula, $contract);

        $calculation->load(['formulaVersion', 'contract']);

        return response()->json(
            new CommissionCalculationResource($calculation),
            Response::HTTP_OK
        );
    }
}
