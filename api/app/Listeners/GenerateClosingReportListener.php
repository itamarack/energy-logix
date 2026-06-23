<?php

namespace App\Listeners;

use App\Events\FormulaVersionDeactivated;
use App\Jobs\GenerateClosingReport;
use App\Models\CommissionCalculation;
use Illuminate\Support\Facades\Storage;

class GenerateClosingReportListener
{
    public function handle(FormulaVersionDeactivated $event): void
    {
        if (
            CommissionCalculation::query()->where('formula_version_id', $event->formulaVersion->id)->doesntExist() ||
            Storage::disk('local')->exists("reports/formula_{$event->formulaVersion->id}_closing_report.csv")
        ) {
            return;
        }

        GenerateClosingReport::dispatch($event->formulaVersion);
    }
}
