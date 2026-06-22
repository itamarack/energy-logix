<?php

namespace App\Enums;

enum FormulaVariable: string
{
    case AnnualUsage = 'annual_usage';
    case ContractValue = 'contract_value';
    case ContractLength = 'contract_length';
    case RiskScore = 'risk_score';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
