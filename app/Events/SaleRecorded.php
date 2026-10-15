<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class SaleRecorded implements ShouldDispatchAfterCommit
{
    public function __construct(public Sale $sale) {}
}
