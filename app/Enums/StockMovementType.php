<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    /**
     * Get the display label for the movement type.
     */
    public function label(): string
    {
        return match ($this) {
            self::In => __('Stock in'),
            self::Out => __('Stock out'),
            self::Adjustment => __('Adjustment'),
        };
    }
}
