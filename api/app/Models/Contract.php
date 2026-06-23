<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'annual_usage', 'contract_value', 'contract_length', 'risk_score'])]
/**
 * @property-read CommissionCalculation|null $latestCommission
 */
class Contract extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'annual_usage' => 'float',
            'contract_value' => 'float',
            'contract_length' => 'integer',
            'risk_score' => 'float',
        ];
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function latestCommission(): HasOne
    {
        return $this->hasOne(CommissionCalculation::class)->latestOfMany('calculated_at');
    }
}
