<?php

namespace App\Repositories;

use App\Models\PaymentMethod;

/**
 * @extends OutletScopedRepository<PaymentMethod>
 */
class PaymentMethodRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return PaymentMethod::class;
    }
}
