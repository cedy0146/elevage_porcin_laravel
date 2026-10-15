<?php

namespace App\Enums;

enum HealthEventType: string
{
    case Vaccination = 'vaccination';
    case Treatment = 'treatment';
    case Observation = 'observation';
}
