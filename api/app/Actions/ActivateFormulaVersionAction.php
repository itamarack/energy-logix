<?php

namespace App\Actions;

use App\Models\FormulaVersion;
use Illuminate\Support\Facades\DB;

class ActivateFormulaVersionAction
{
    /**
     * Set the given formula version as active, deactivating all others.
     */
    public function execute(FormulaVersion $formulaVersion): void
    {
        DB::transaction(function () use ($formulaVersion): void {
            // Deactivate all other formulas
            FormulaVersion::query()
                ->where('id', '!=', $formulaVersion->id)
                ->update(['is_active' => false]);

            // Activate the targeted formula
            $formulaVersion->is_active = true;
            $formulaVersion->save();
        });
    }
}
