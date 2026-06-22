<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimulationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'affected_contract_count' => $this->affected_contract_count,
            'current_total_commission' => $this->current_total_commission,
            'new_total_commission' => $this->new_total_commission,
            'difference' => $this->difference,
        ];
    }
}
