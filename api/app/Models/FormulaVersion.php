<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'version_number', 'expression', 'variables', 'is_active'])]
class FormulaVersion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }
}
