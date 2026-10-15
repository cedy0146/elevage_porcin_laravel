<?php

namespace App\Events;

use App\Models\Mortality;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class MortalityRecorded implements ShouldDispatchAfterCommit
{
    public function __construct(public Mortality $mortality) {}
}
