<?php

namespace App\Repositories;

use App\Models\Supplier;

/**
 * @extends OutletScopedRepository<Supplier>
 */
class SupplierRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }
}
