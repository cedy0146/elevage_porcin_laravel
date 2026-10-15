<?php

namespace App\Enums;

enum LotStatus: string
{
    case Active = 'active';

    case Completed = 'completed';

    case Cancelled = 'cancelled';
}
