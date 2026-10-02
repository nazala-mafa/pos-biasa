<?php

namespace App\Repositories;

use App\Models\Customer;

/**
 * @extends OutletScopedRepository<Customer>
 */
class CustomerRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Customer::class;
    }
}
