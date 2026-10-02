<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qris = 'qris';

    /**
     * Get the display label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::Transfer => __('Bank transfer'),
            self::Qris => __('QRIS'),
        };
    }
}
