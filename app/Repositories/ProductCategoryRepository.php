<?php

namespace App\Repositories;

use App\Models\ProductCategory;

/**
 * @extends OutletScopedRepository<ProductCategory>
 */
class ProductCategoryRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return ProductCategory::class;
    }
}
