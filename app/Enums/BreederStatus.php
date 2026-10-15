<?php

namespace App\Enums;

enum BreederStatus: string
{
    case Active = 'active';
    case Pregnant = 'pregnant';
    case Nursing = 'nursing';
    case Retired = 'retired';
}
