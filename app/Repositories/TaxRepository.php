<?php

namespace App\Repositories;

use App\Models\Tax;

/**
 * @extends OutletScopedRepository<Tax>
 */
class TaxRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Tax::class;
    }
}
