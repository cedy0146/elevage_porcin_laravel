<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';

    case Entry = 'entry';

    case Exit = 'exit';

    case Consumption = 'consumption';

    case Adjustment = 'adjustment';

    /**
     * Indique si ce type de mouvement augmente le stock disponible.
     */
    public function increasesStock(): bool
    {
        return match ($this) {
            self::Initial, self::Entry => true,
            self::Exit, self::Consumption, self::Adjustment => false,
        };
    }
}
