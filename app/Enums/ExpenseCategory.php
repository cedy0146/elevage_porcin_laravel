<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Feed = 'feed';

    case Labor = 'labor';

    case Veterinary = 'veterinary';

    case Transport = 'transport';

    case Energy = 'energy';

    case Purchase = 'purchase';

    case Other = 'other';
}
