<?php

namespace App\Events;

use App\Models\FormulaVersion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormulaVersionDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FormulaVersion $formulaVersion,
    ) {}
}
