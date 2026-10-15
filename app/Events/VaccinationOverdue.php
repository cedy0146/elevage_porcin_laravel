<?php

namespace App\Events;

use App\Models\HealthEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VaccinationOverdue implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public HealthEvent $event) {}
}
