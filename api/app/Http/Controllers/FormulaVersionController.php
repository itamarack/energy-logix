<?php

namespace App\Http\Controllers;

use App\Actions\FormulaVersionAction;
use App\DTOs\FormulaVersionData;
use App\Http\Requests\StoreFormulaVersionRequest;
use App\Http\Requests\UpdateFormulaVersionRequest;
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
        $formulaVersions = FormulaVersion::query()->orderBy('version_number', 'asc')->paginate(10);

        return FormulaVersionResource::collection($formulaVersions)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(StoreFormulaVersionRequest $request, FormulaVersionAction $action): JsonResponse
    {
        $formulaVersionData = FormulaVersionData::fromArray($request->validated());
        $formulaVersion = $action->create($formulaVersionData)->execute();

        return (new FormulaVersionResource($formulaVersion))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateFormulaVersionRequest $request, FormulaVersion $formulaVersion, FormulaVersionAction $action): JsonResponse
    {
        $formulaVersionData = FormulaVersionData::fromArray($request->validated());
        $updatedFormulaVersion = $action->update($formulaVersion, $formulaVersionData)->execute();

        return (new FormulaVersionResource($updatedFormulaVersion))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show(FormulaVersion $formulaVersion): JsonResponse
    {
        return (new FormulaVersionResource($formulaVersion))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function activate(FormulaVersion $formulaVersion, FormulaVersionAction $action): JsonResponse
    {
        $action->activate($formulaVersion)->execute();

        return (new FormulaVersionResource($formulaVersion->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function deactivate(FormulaVersion $formulaVersion, FormulaVersionAction $action): JsonResponse
    {
        $action->deactivate($formulaVersion)->execute();

        return (new FormulaVersionResource($formulaVersion->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function simulate(FormulaVersion $formulaVersion): JsonResponse
    {
        $result = $this->simulator->simulate($formulaVersion);

        return (new SimulationResultResource($result))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
