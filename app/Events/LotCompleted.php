<?php

namespace App\Events;

use App\Models\PigLot;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LotCompleted implements ShouldDispatchAfterCommit
{
    public function __construct(public PigLot $lot) {}
}
