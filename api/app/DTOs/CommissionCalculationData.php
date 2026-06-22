<?php

namespace App\DTOs;

class CommissionCalculationData
{
    public function __construct(
        public readonly array $inputValues,
        public readonly array $steps,
        public readonly float $result,
    ) {}
}
