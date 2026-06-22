<?php

namespace App\Http\Controllers;

use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Http\Resources\FormulaVersionResource;
use App\Models\FormulaVersion;
use App\Services\CommissionSimulator;
use App\Services\FormulaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FormulaVersionController extends Controller
{
    public function __construct(
        private readonly FormulaValidator $validator,
        private readonly CommissionSimulator $simulator,
    ) {}

    public function index(Request $request): InertiaResponse|AnonymousResourceCollection
    {
        $formulaVersions = FormulaVersion::orderBy('version_number')->get();

        if ($request->wantsJson()) {
            return FormulaVersionResource::collection($formulaVersions);
        }

        return Inertia::render('FormulaVersions/Index', [
            'formulaVersions' => FormulaVersionResource::collection($formulaVersions)->resolve(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('FormulaVersions/Create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expression' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['required', 'string'],
            'variables.*.expression' => ['required', 'string'],
        ]);

        $expression = $validated['expression'];
        $variables = $validated['variables'] ?? [];

        try {
            $this->validator->validate($expression, $variables);
        } catch (ParseException|UndefinedVariableException|CircularDependencyException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['expression' => $e->getMessage()])->withInput();
        }

        $nextVersionNumber = (FormulaVersion::max('version_number') ?? 0) + 1;

        $formulaVersion = FormulaVersion::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'version_number' => $nextVersionNumber,
            'expression' => $expression,
            'variables' => $variables,
            'is_active' => false,
        ]);

        if ($request->wantsJson()) {
            return (new FormulaVersionResource($formulaVersion))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()->route('formula-versions.index')
            ->with('success', 'Formula version created successfully.');
    }

    public function show(Request $request, FormulaVersion $formulaVersion): InertiaResponse|FormulaVersionResource
    {
        if ($request->wantsJson()) {
            return new FormulaVersionResource($formulaVersion);
        }

        return Inertia::render('FormulaVersions/Show', [
            'formulaVersion' => (new FormulaVersionResource($formulaVersion))->resolve(),
        ]);
    }

    public function activate(FormulaVersion $formulaVersion): FormulaVersionResource
    {
        DB::transaction(function () use ($formulaVersion): void {
            FormulaVersion::query()->update(['is_active' => false]);
            $formulaVersion->update(['is_active' => true]);
        });

        return new FormulaVersionResource($formulaVersion->fresh());
    }

    public function simulate(FormulaVersion $formulaVersion): JsonResponse
    {
        try {
            $result = $this->simulator->simulate($formulaVersion);
        } catch (ParseException|UndefinedVariableException|CircularDependencyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'affected_contract_count' => $result->affected_contract_count,
            'current_total_commission' => $result->current_total_commission,
            'new_total_commission' => $result->new_total_commission,
            'difference' => $result->difference,
        ]);
    }
}
