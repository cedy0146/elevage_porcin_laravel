<?php

namespace App\Events;

use App\Models\Feeding;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class FeedingRecorded implements ShouldDispatchAfterCommit
{
    public function __construct(public Feeding $feeding) {}
}
