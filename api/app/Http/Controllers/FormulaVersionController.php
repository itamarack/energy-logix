<?php

namespace App\Http\Controllers;

use App\Actions\FormulaVersionAction;
use App\DTOs\FormulaVersionData;
use App\Http\Requests\StoreFormulaVersionRequest;
use App\Http\Resources\FormulaVersionResource;
use App\Http\Resources\SimulationResultResource;
use App\Models\FormulaVersion;
use App\Services\CommissionSimulator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FormulaVersionController extends Controller
{
    public function __construct(
        private readonly CommissionSimulator $simulator,
    ) {}

    public function index(): JsonResponse
    {
        $formulaVersions = FormulaVersion::query()->orderBy('version_number', 'asc')->get();

        return response()->json(
            FormulaVersionResource::collection($formulaVersions),
            Response::HTTP_OK
        );
    }

    public function store(StoreFormulaVersionRequest $request, FormulaVersionAction $action): JsonResponse
    {
        $formulaVersionData = FormulaVersionData::fromArray($request->validated());
        $formulaVersion = $action->create($formulaVersionData)->execute();

        return response()->json(
            new FormulaVersionResource($formulaVersion),
            Response::HTTP_CREATED
        );
    }

    public function show(FormulaVersion $formulaVersion): JsonResponse
    {
        return response()->json(
            new FormulaVersionResource($formulaVersion),
            Response::HTTP_OK
        );
    }

    public function activate(FormulaVersion $formulaVersion, FormulaVersionAction $action): JsonResponse
    {
        $action->activate($formulaVersion)->execute();

        return response()->json(
            new FormulaVersionResource($formulaVersion->fresh()),
            Response::HTTP_OK
        );
    }

    public function simulate(FormulaVersion $formulaVersion): JsonResponse
    {
        $result = $this->simulator->simulate($formulaVersion);

        return response()->json(
            new SimulationResultResource($result),
            Response::HTTP_OK
        );
    }
}
