<?php

namespace App\Listeners;

use App\Events\FormulaVersionActivated;
use App\Jobs\CalculateCommission;
use App\Models\Contract;

class RecalculateCommissionsListener
{
    public function handle(FormulaVersionActivated $event): void
    {
        Contract::query()->chunkById(100, function ($contracts) use ($event) {
            foreach ($contracts as $contract) {
                CalculateCommission::dispatch($contract, $event->formulaVersion);
            }
        });
    }
}
