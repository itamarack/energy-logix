<?php

namespace App\Jobs;

use App\Actions\CalculateContractCommissionAction;
use App\Models\Contract;
use App\Models\FormulaVersion;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateCommission implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly Contract $contract,
        public readonly FormulaVersion $formulaVersion,
    ) {}

    public function handle(CalculateContractCommissionAction $calculateAction): void
    {
        $calculateAction->execute($this->formulaVersion, $this->contract);
    }

    public function failed(\Throwable $exception): void
    {
        app(ExceptionHandler::class)->report($exception);
    }
}
