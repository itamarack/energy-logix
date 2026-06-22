<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionCalculation extends Model
{
    use HasFactory;

    public const RESULT = 'RESULT';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input_values' => 'array',
            'calculation_steps' => 'array',
            'result' => 'float',
            'calculated_at' => 'datetime',
        ];
    }

    public function formulaVersion(): BelongsTo
    {
        return $this->belongsTo(FormulaVersion::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
