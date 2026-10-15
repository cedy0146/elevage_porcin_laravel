<?php

namespace App\Events;

use App\Models\FeedType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class StockCritical implements ShouldDispatchAfterCommit
{
    public function __construct(public FeedType $feedType) {}
}
