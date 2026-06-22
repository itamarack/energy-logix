<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'annual_usage' => $this->annual_usage,
            'contract_value' => $this->contract_value,
            'contract_length' => $this->contract_length,
            'risk_score' => $this->risk_score,
            'created_at' => $this->created_at,
        ];
    }
}
