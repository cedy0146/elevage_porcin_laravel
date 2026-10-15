<?php

namespace App\Events;

use App\Models\Feeding;

class FeedingRecorded
{
    public function __construct(public Feeding $feeding) {}
}
