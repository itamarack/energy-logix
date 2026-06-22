<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'formula_version_id' => $this->formula_version_id,
            'contract_id' => $this->contract_id,
            'input_values' => $this->input_values,
            'calculation_steps' => $this->calculation_steps,
            'result' => $this->result,
            'calculated_at' => $this->calculated_at,
            'formula_version' => new FormulaVersionResource($this->whenLoaded('formulaVersion')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
        ];
    }
}
