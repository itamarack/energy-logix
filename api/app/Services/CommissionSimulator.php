<?php

namespace App\Services;

use App\DTOs\SimulationResult;
use App\Models\Contract;
use App\Models\FormulaVariable;
use App\Models\FormulaVersion;
use Illuminate\Support\Facades\Cache;

class CommissionSimulator
{
    public function __construct(
        private readonly FormulaValidator $validator,
        private readonly FormulaEvaluator $evaluator,
        private readonly DependencyResolver $resolver,
    ) {}

    public function simulate(FormulaVersion $targetFormula): SimulationResult
    {
        $this->validator->validate($targetFormula->expression, $targetFormula->variables ?? []);

        $activeFormula = FormulaVersion::query()->where('is_active', true)->first();
        $contracts = Contract::all();

        $currentTotal = 0.0;
        $newTotal = 0.0;

        foreach ($contracts as $contract) {
            if ($activeFormula !== null) {
                $currentTotal += $this->calculateDryRun($activeFormula, $contract);
            }

            $newTotal += $this->calculateDryRun($targetFormula, $contract);
        }

        return new SimulationResult(
            affected_contract_count: $contracts->count(),
            current_total_commission: $currentTotal,
            new_total_commission: $newTotal,
            difference: $newTotal - $currentTotal,
        );
    }

    private function calculateDryRun(FormulaVersion $formula, Contract $contract): float
    {
        $baseVariables = Cache::remember('formula_variables', 3600, function () {
            return FormulaVariable::pluck('name')->toArray();
        });

        $variableMap = $contract->only($baseVariables);

        /** @var array<int, array{name: string, expression: string}> $variables */
        $variables = $formula->variables ?? [];
        $orderedNames = $this->resolver->resolve($variables);

        /** @var array<string, string> $variablesByName */
        $variablesByName = [];
        foreach ($variables as $variable) {
            $variablesByName[$variable['name']] = $variable['expression'];
        }

        foreach ($orderedNames as $name) {
            $value = $this->evaluator->evaluate($variablesByName[$name], $variableMap);
            $variableMap[$name] = $value;
        }

        return $this->evaluator->evaluate($formula->expression, $variableMap);
    }
}
