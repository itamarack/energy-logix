<?php

namespace App\Actions;

use App\DTOs\FormulaVersionData;
use App\Events\FormulaVersionActivated;
use App\Models\FormulaVersion;
use Closure;
use Illuminate\Support\Facades\DB;

class FormulaVersionAction
{
    protected Closure $operation;

    public function create(FormulaVersionData $data): self
    {
        $this->operation = function () use ($data) {
            $nextVersionNumber = (FormulaVersion::query()->max('version_number') ?? 0) + 1;

            return FormulaVersion::create(array_merge($data->toArray(), [
                'version_number' => $nextVersionNumber,
                'is_active' => false,
            ]));
        };

        return $this;
    }

    public function update(FormulaVersion $formulaVersion, FormulaVersionData $data): self
    {
        $this->operation = function () use ($formulaVersion, $data) {
            $formulaVersion->fill($data->toArray())->save();

            return $formulaVersion->fresh();
        };

        return $this;
    }

    public function activate(FormulaVersion $formulaVersion): self
    {
        $this->operation = function () use ($formulaVersion) {
            FormulaVersion::query()->whereKeyNot($formulaVersion->getKey())->update(['is_active' => false]);
            $formulaVersion->is_active = true;
            $formulaVersion->save();

            DB::afterCommit(function () use ($formulaVersion) {
                FormulaVersionActivated::dispatch($formulaVersion);
            });
        };

        return $this;
    }

    public function deactivate(FormulaVersion $formulaVersion): self
    {
        $this->operation = function () use ($formulaVersion) {
            $formulaVersion->is_active = false;
            $formulaVersion->save();
        };

        return $this;
    }

    public function execute(): mixed
    {
        return DB::transaction($this->operation);
    }
}
