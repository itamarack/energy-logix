<?php

namespace App\Jobs;

use App\Models\CommissionCalculation;
use App\Models\FormulaVersion;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;

class GenerateClosingReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly FormulaVersion $formulaVersion,
    ) {}

    public function handle(): void
    {
        $filename = "reports/formula_{$this->formulaVersion->id}_closing_report.csv";
        Storage::makeDirectory('reports');

        $writer = SimpleExcelWriter::create(Storage::path($filename))
            ->addHeader([
                'Calculation ID',
                'Contract ID',
                'Calculated At',
                'Result',
            ]);

        CommissionCalculation::query()
            ->where('formula_version_id', $this->formulaVersion->id)
            ->lazyById()
            ->each(function (CommissionCalculation $calculation) use ($writer) {
                $writer->addRow([
                    'Calculation ID' => $calculation->id,
                    'Contract ID' => $calculation->contract_id,
                    'Calculated At' => Carbon::parse($calculation->calculated_at)->toIso8601String(),
                    'Result' => $calculation->result,
                ]);
            });

        $writer->close();
    }
}
