<?php

namespace App\Repositories;

use App\Models\Unit;

/**
 * @extends OutletScopedRepository<Unit>
 */
class UnitRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Unit::class;
    }
}
