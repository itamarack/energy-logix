<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContractResource;
use App\Jobs\CalculateCommission;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ContractController extends Controller
{
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

    public function calculate(Contract $contract): JsonResponse
    {
        $activeFormula = FormulaVersion::where('is_active', true)->first();

        if ($activeFormula === null) {
            return response()->json(['message' => 'No active formula version exists'], 422);
        }

        CalculateCommission::dispatch($contract, $activeFormula);

        return response()->json(['message' => 'Calculation queued'], 202);
    }
}
