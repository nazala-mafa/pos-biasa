<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends OutletScopedRepository<Product>
 */
class ProductRepository extends OutletScopedRepository
{
    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function relations(): array
    {
        return ['productCategory:id,name', 'unit:id,name,abbreviation', 'tax:id,name,rate'];
    }

    /**
     * Products of the outlet with their current stock level.
     *
     * @return Collection<int, Product>
     */
    public function withStock(Team $outlet, bool $activeOnly = false): Collection
    {
        return $outlet->products()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->with('unit:id,abbreviation')
            ->withSum('stockMovements as stock', 'quantity')
            ->orderBy('name')
            ->get();
    }
}
