<?php

namespace App\DTOs;

final class SimulationResult
{
    public function __construct(
        public readonly int $affected_contract_count,
        public readonly float $current_total_commission,
        public readonly float $new_total_commission,
        public readonly float $difference,
    ) {}
}
