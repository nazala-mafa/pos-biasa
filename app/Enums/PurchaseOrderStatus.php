<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Ordered = 'ordered';
    case Partial = 'partial';
    case Received = 'received';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ordered => __('Ordered'),
            self::Partial => __('Partially received'),
            self::Received => __('Received'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * Whether goods can still be received against an order in this status.
     */
    public function isReceivable(): bool
    {
        return $this === self::Ordered || $this === self::Partial;
    }
}
