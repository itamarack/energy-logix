<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\FormulaVersion
 */
class FormulaVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'version_number' => $this->version_number,
            'expression' => $this->expression,
            'variables' => $this->variables,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
