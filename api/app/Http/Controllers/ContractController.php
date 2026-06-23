<?php

namespace App\Http\Controllers;

use App\Actions\CalculateContractCommissionAction;
use App\Http\Resources\CommissionCalculationResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ContractController extends Controller
{
    public function __construct(
        private readonly CalculateContractCommissionAction $calculateAction,
    ) {}

    public function index(): JsonResponse
    {
        return ContractResource::collection(Contract::query()->with('latestCommission')->paginate(10))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show(Contract $contract): JsonResponse
    {
        $contract->load('latestCommission');

        return (new ContractResource($contract))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function calculations(Contract $contract): JsonResponse
    {
        $calculations = $contract->commissionCalculations()
            ->with('formulaVersion')
            ->orderBy('calculated_at', 'desc')
            ->paginate(10);

        return CommissionCalculationResource::collection($calculations)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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

        $calculation = $this->calculateAction->execute($activeFormula, $contract);

        $calculation->load(['formulaVersion', 'contract']);

        return (new CommissionCalculationResource($calculation))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
