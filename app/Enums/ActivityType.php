<?php

namespace App\Enums;

enum ActivityType: string
{
    case Cleaning = 'cleaning';

    case Maintenance = 'maintenance';

    case Transfer = 'transfer';

    case Treatment = 'treatment';

    case Other = 'other';
}
