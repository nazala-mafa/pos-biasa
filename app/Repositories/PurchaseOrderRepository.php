<?php

namespace App\Repositories;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\PurchaseOrder;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Concerns\GeneratesDocumentNumbers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderRepository
{
    use GeneratesDocumentNumbers;

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->purchaseOrders()
            ->with(['supplier:id,name', 'user:id,name'])
            ->withCount('items')
            ->latest('ordered_at')
            ->latest('id')
            ->get();
    }

    public function findOrFail(Team $outlet, int $id): PurchaseOrder
    {
        return $outlet->purchaseOrders()
            ->with(['supplier:id,name', 'user:id,name', 'items.product:id,sku,name', 'receipts.user:id,name', 'receipts.items'])
            ->findOrFail($id);
    }

    /**
     * @param  array{supplier_id: int, ordered_at: string, expected_at?: string|null, note?: string|null, items: array<int, array{product_id: int, quantity: float|int|string, unit_cost: float|int|string}>}  $data
     */
    public function create(Team $outlet, User $user, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($outlet, $user, $data) {
            $lines = collect($data['items'])->map(fn (array $item) => [
                'product_id' => $item['product_id'],
                'quantity' => (float) $item['quantity'],
                'unit_cost' => (float) $item['unit_cost'],
                'subtotal' => round((float) $item['quantity'] * (float) $item['unit_cost'], 2),
            ]);

            $order = $outlet->purchaseOrders()->create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $user->id,
                'number' => $this->nextDocumentNumber($outlet->purchaseOrders(), 'PO'),
                'status' => PurchaseOrderStatus::Ordered,
                'ordered_at' => $data['ordered_at'],
                'expected_at' => $data['expected_at'] ?? null,
                'total' => $lines->sum('subtotal'),
                'note' => $data['note'] ?? null,
            ]);

            $order->items()->createMany($lines->all());

            return $order;
        });
    }

    /**
     * Book goods against an order: store the receipt, put the quantities into stock and move the
     * order to partially/fully received.
     *
     * @param  array{received_at: string, note?: string|null, items: array<int, array{purchase_order_item_id: int, quantity: float|int|string}>}  $data
     *
     * @throws ValidationException
     */
    public function receive(Team $outlet, User $user, PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($outlet, $user, $order, $data) {
            $order = $outlet->purchaseOrders()->with('items')->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->isReceivable()) {
                throw ValidationException::withMessages(['items' => __('This purchase order can no longer receive goods.')]);
            }

            $received = collect($data['items'])
                ->map(fn (array $item) => [...$item, 'quantity' => (float) $item['quantity']])
                ->filter(fn (array $item) => $item['quantity'] > 0);

            if ($received->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('Enter the quantity received for at least one item.')]);
            }

            $receipt = $outlet->goodsReceipts()->create([
                'purchase_order_id' => $order->id,
                'user_id' => $user->id,
                'number' => $this->nextDocumentNumber($outlet->goodsReceipts(), 'GR'),
                'received_at' => $data['received_at'],
                'note' => $data['note'] ?? null,
            ]);

            foreach ($received as $line) {
                $item = $order->items->firstWhere('id', $line['purchase_order_item_id']);
                $remaining = $item === null ? 0.0 : (float) $item->quantity - (float) $item->received_quantity;

                if ($item === null || $line['quantity'] > $remaining) {
                    throw ValidationException::withMessages(['items' => __('The quantity received exceeds the quantity ordered.')]);
                }

                $item->increment('received_quantity', $line['quantity']);
                $receipt->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $line['quantity'],
                ]);
                $outlet->stockMovements()->create([
                    'product_id' => $item->product_id,
                    'supplier_id' => $order->supplier_id,
                    'user_id' => $user->id,
                    'type' => StockMovementType::In,
                    'quantity' => $line['quantity'],
                    'note' => "{$receipt->number} / {$order->number}",
                ]);
            }

            $order->load('items');
            $fullyReceived = $order->items->every(fn ($item) => (float) $item->received_quantity >= (float) $item->quantity);
            $order->update(['status' => $fullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::Partial]);

            return $order;
        });
    }

    /**
     * @throws ValidationException
     */
    public function cancel(PurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::Ordered) {
            throw ValidationException::withMessages(['status' => __('Only orders without received goods can be cancelled.')]);
        }

        $order->update(['status' => PurchaseOrderStatus::Cancelled]);
    }
}
