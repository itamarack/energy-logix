<?php

namespace App\Actions;

use App\DTOs\FormulaVersionData;
use App\Models\FormulaVersion;

class CreateFormulaVersionAction
{
    public function execute(FormulaVersionData $data): FormulaVersion
    {
        $nextVersionNumber = (FormulaVersion::query()->max('version_number') ?? 0) + 1;

        return FormulaVersion::create([
            'name' => $data->name,
            'description' => $data->description,
            'version_number' => $nextVersionNumber,
            'expression' => $data->expression,
            'variables' => $data->variables,
            'is_active' => false,
        ]);
    }
}
