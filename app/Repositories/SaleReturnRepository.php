<?php

namespace App\Repositories;

use App\Enums\StockMovementType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Concerns\GeneratesDocumentNumbers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnRepository
{
    use GeneratesDocumentNumbers;

    /**
     * @return Collection<int, SaleReturn>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->saleReturns()
            ->with(['sale:id,invoice_number', 'user:id,name'])
            ->withCount('items')
            ->latest()
            ->latest('id')
            ->get();
    }

    public function findOrFail(Team $outlet, int $id): SaleReturn
    {
        return $outlet->saleReturns()
            ->with(['sale:id,invoice_number', 'user:id,name', 'items.saleItem'])
            ->findOrFail($id);
    }

    /**
     * Sale lines with the quantity that was already returned.
     *
     * @return \Illuminate\Support\Collection<int, array{item: SaleItem, returned: float, returnable: float}>
     */
    public function returnableLines(Sale $sale): \Illuminate\Support\Collection
    {
        $returned = DB::table('sale_return_items')
            ->whereIn('sale_item_id', $sale->items->pluck('id'))
            ->groupBy('sale_item_id')
            ->selectRaw('sale_item_id, sum(quantity) as quantity')
            ->pluck('quantity', 'sale_item_id');

        return $sale->items->map(fn (SaleItem $item) => [
            'item' => $item,
            'returned' => (float) ($returned[$item->id] ?? 0),
            'returnable' => (float) $item->quantity - (float) ($returned[$item->id] ?? 0),
        ]);
    }

    /**
     * Take sold goods back: refund each line pro rata (price plus tax), return the quantities to
     * stock and store the return document.
     *
     * @param  array{reason?: string|null, items: array<int, array{sale_item_id: int, quantity: float|int|string}>}  $data
     *
     * @throws ValidationException
     */
    public function create(Team $outlet, User $user, Sale $sale, array $data): SaleReturn
    {
        return DB::transaction(function () use ($outlet, $user, $sale, $data) {
            $sale = $outlet->sales()->with('items')->lockForUpdate()->findOrFail($sale->id);
            $lines = $this->returnableLines($sale)->keyBy(fn (array $line) => $line['item']->id);

            $returned = collect($data['items'])
                ->map(fn (array $item) => ['sale_item_id' => (int) $item['sale_item_id'], 'quantity' => (float) $item['quantity']])
                ->filter(fn (array $item) => $item['quantity'] > 0);

            if ($returned->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('Enter the quantity returned for at least one item.')]);
            }

            $return = $outlet->saleReturns()->create([
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'number' => $this->nextDocumentNumber($outlet->saleReturns(), 'RET'),
                'reason' => $data['reason'] ?? null,
                'total' => 0,
            ]);

            $total = 0.0;

            foreach ($returned as $line) {
                $saleLine = $lines->get($line['sale_item_id']);

                if ($saleLine === null || $line['quantity'] > $saleLine['returnable']) {
                    throw ValidationException::withMessages(['items' => __('The quantity returned exceeds the quantity sold.')]);
                }

                /** @var SaleItem $item */
                $item = $saleLine['item'];
                $amount = round(((float) $item->subtotal + (float) $item->tax_amount) / (float) $item->quantity * $line['quantity'], 2);
                $total += $amount;

                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $line['quantity'],
                    'amount' => $amount,
                ]);

                if ($item->product_id !== null) {
                    $outlet->stockMovements()->create([
                        'product_id' => $item->product_id,
                        'user_id' => $user->id,
                        'type' => StockMovementType::In,
                        'quantity' => $line['quantity'],
                        'note' => "{$return->number} / {$sale->invoice_number}",
                    ]);
                }
            }

            $return->update(['total' => round($total, 2)]);

            return $return;
        });
    }
}
