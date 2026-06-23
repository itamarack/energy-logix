<?php

namespace App\Actions;

use App\Models\FormulaVersion;
use Illuminate\Support\Facades\DB;

class ActivateFormulaVersionAction
{
    public function execute(FormulaVersion $formulaVersion): void
    {
        DB::transaction(function () use ($formulaVersion): void {
            FormulaVersion::query()
                ->where('id', '!=', $formulaVersion->id)
                ->update(['is_active' => false]);

            $formulaVersion->is_active = true;
            $formulaVersion->save();
        });
    }
}
