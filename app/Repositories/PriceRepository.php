<?php

namespace App\Repositories;

use App\Models\Price;

/**
 * @extends OutletScopedRepository<Price>
 */
class PriceRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Price::class;
    }
}
