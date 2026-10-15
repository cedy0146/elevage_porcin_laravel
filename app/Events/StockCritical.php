<?php

namespace App\Events;

use App\Models\FeedType;

class StockCritical
{
    public function __construct(public FeedType $feedType) {}
}
