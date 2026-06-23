<?php

namespace App\Http\Resources;

use App\Models\CommissionCalculation;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CommissionCalculation|null $latest */
        $latest = $this->latestCommission;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'annual_usage' => $this->annual_usage,
            'contract_value' => $this->contract_value,
            'contract_length' => $this->contract_length,
            'risk_score' => $this->risk_score,
            'created_at' => $this->created_at,
            'last_commission_result' => $latest?->result,
        ];
    }
}
