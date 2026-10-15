<?php

namespace App\Enums;

enum MatingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Farrowed = 'farrowed';
}
