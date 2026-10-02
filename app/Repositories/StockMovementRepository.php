<?php

namespace App\Repositories;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class StockMovementRepository
{
    /**
     * @return Collection<int, StockMovement>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->stockMovements()
            ->with(['product:id,sku,name', 'supplier:id,name', 'user:id,name'])
            ->latest()
            ->latest('id')
            ->get();
    }

    public function currentStock(Team $outlet, int $productId): float
    {
        return (float) $outlet->stockMovements()->where('product_id', $productId)->sum('quantity');
    }

    /**
     * @param  array{product_id: int, supplier_id: int|null, user_id: int, type: StockMovementType, quantity: float, note: string|null}  $data
     */
    public function create(Team $outlet, array $data): StockMovement
    {
        return $outlet->stockMovements()->create($data);
    }
}
