<?php

namespace App\Repositories;

use App\Enums\StockMovementType;
use App\Models\StockOpname;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Concerns\GeneratesDocumentNumbers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StockOpnameRepository
{
    use GeneratesDocumentNumbers;

    /**
     * @return Collection<int, StockOpname>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->stockOpnames()
            ->with('user:id,name')
            ->withCount('items')
            ->withSum('items as difference_total', 'difference')
            ->latest('counted_at')
            ->latest('id')
            ->get();
    }

    public function findOrFail(Team $outlet, int $id): StockOpname
    {
        return $outlet->stockOpnames()
            ->with(['user:id,name', 'items.product:id,sku,name', 'items.product.unit:id,abbreviation'])
            ->findOrFail($id);
    }

    /**
     * Record a physical count: compare every counted product with the book stock at this moment
     * and post the difference as an adjustment so stock matches the count.
     *
     * @param  array{counted_at: string, note?: string|null, items: array<int, array{product_id: int, counted_quantity: float|int|string}>}  $data
     */
    public function create(Team $outlet, User $user, array $data): StockOpname
    {
        return DB::transaction(function () use ($outlet, $user, $data) {
            $opname = $outlet->stockOpnames()->create([
                'user_id' => $user->id,
                'number' => $this->nextDocumentNumber($outlet->stockOpnames(), 'SO'),
                'counted_at' => $data['counted_at'],
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $system = (float) $outlet->stockMovements()->where('product_id', $line['product_id'])->sum('quantity');
                $counted = (float) $line['counted_quantity'];
                $difference = round($counted - $system, 2);

                $opname->items()->create([
                    'product_id' => $line['product_id'],
                    'system_quantity' => $system,
                    'counted_quantity' => $counted,
                    'difference' => $difference,
                ]);

                if ($difference != 0.0) {
                    $outlet->stockMovements()->create([
                        'product_id' => $line['product_id'],
                        'user_id' => $user->id,
                        'type' => StockMovementType::Adjustment,
                        'quantity' => $difference,
                        'note' => $opname->number,
                    ]);
                }
            }

            return $opname;
        });
    }
}
