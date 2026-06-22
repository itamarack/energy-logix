<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommissionCalculationResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\FormulaVersion;
use App\Services\CommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ContractController extends Controller
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
    ) {}

    public function index(Request $request): InertiaResponse|AnonymousResourceCollection
    {
        $contracts = Contract::all();

        if ($request->wantsJson()) {
            return ContractResource::collection($contracts);
        }

        return Inertia::render('Contracts/Index', [
            'contracts' => ContractResource::collection($contracts)->resolve(),
        ]);
    }

    public function calculate(Contract $contract): Response
    {
        $activeFormula = FormulaVersion::where('is_active', true)->first();

        if ($activeFormula === null) {
            return response()->json(['message' => 'No active formula version exists'], 422);
        }

        $calculation = $this->calculator->calculate($activeFormula, $contract);

        return (new CommissionCalculationResource($calculation))
            ->response()
            ->setStatusCode(200);
    }
}
