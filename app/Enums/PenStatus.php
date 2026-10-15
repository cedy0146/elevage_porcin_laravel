<?php

namespace App\Enums;

enum PenStatus: string
{
    case Free = 'free';

    case Occupied = 'occupied';

    case Maintenance = 'maintenance';
}
