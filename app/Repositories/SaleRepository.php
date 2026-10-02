<?php

namespace App\Repositories;

use App\Enums\StockMovementType;
use App\Models\Sale;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleRepository
{
    /**
     * @return Collection<int, Sale>
     */
    public function history(Team $outlet): Collection
    {
        return $outlet->sales()
            ->with(['customer:id,name', 'user:id,name'])
            ->withCount('items')
            ->latest()
            ->latest('id')
            ->get();
    }

    public function findOrFail(Team $outlet, int $id): Sale
    {
        return $outlet->sales()
            ->with(['customer:id,name', 'user:id,name', 'items'])
            ->findOrFail($id);
    }

    /**
     * Record a sale: price the lines from the current product prices, take the sold quantities
     * out of stock and store the invoice, all in one transaction.
     *
     * @param  array{customer_id?: int|null, payment_method_id: int, discount?: float|int|string|null, paid: float|int|string, note?: string|null, items: array<int, array{product_id: int, quantity: float|int|string}>}  $data
     *
     * @throws ValidationException
     */
    public function checkout(Team $outlet, User $cashier, array $data): Sale
    {
        return DB::transaction(function () use ($outlet, $cashier, $data) {
            $paymentMethod = $outlet->paymentMethods()->where('is_active', true)->find($data['payment_method_id']);

            if ($paymentMethod === null) {
                throw ValidationException::withMessages(['payment_method_id' => __('The selected payment method is not available.')]);
            }

            $quantities = [];
            foreach ($data['items'] as $item) {
                $quantities[$item['product_id']] = ($quantities[$item['product_id']] ?? 0) + (float) $item['quantity'];
            }

            $products = $outlet->products()
                ->with('tax:id,rate')
                ->where('is_active', true)
                ->withSum('stockMovements as stock', 'quantity')
                ->whereKey(array_keys($quantities))
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product === null) {
                    throw ValidationException::withMessages(['items' => __('One of the products is no longer available.')]);
                }

                if ((float) ($product->stock ?? 0) < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => __('Insufficient stock for :product. Current stock: :stock.', ['product' => $product->name, 'stock' => (float) ($product->stock ?? 0)]),
                    ]);
                }

                $lineSubtotal = round((float) $product->selling_price * $quantity, 2);
                $taxRate = (float) ($product->tax?->rate ?? 0);
                $taxAmount = round($lineSubtotal * $taxRate / 100, 2);

                $subtotal += $lineSubtotal;
                $taxTotal += $taxAmount;

                $lines[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $gross = $subtotal + $taxTotal;

            if ($discount > $gross) {
                throw ValidationException::withMessages(['discount' => __('The discount cannot exceed the sale total.')]);
            }

            $total = round($gross - $discount, 2);
            $paid = (float) $data['paid'];

            if ($paid < $total) {
                throw ValidationException::withMessages(['paid' => __('The amount paid is less than the total.')]);
            }

            $sale = $outlet->sales()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $cashier->id,
                'cashier_shift_id' => $outlet->cashierShifts()->where('user_id', $cashier->id)->whereNull('closed_at')->value('id'),
                'invoice_number' => $this->nextInvoiceNumber($outlet),
                'payment_method_id' => $paymentMethod->id,
                'payment_method' => $paymentMethod->name,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_total' => $taxTotal,
                'total' => $total,
                'paid' => $paid,
                'change' => round($paid - $total, 2),
                'note' => $data['note'] ?? null,
            ]);

            $sale->items()->createMany($lines);

            foreach ($lines as $line) {
                $outlet->stockMovements()->create([
                    'product_id' => $line['product_id'],
                    'user_id' => $cashier->id,
                    'type' => StockMovementType::Out,
                    'quantity' => -$line['quantity'],
                    'note' => $sale->invoice_number,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Invoice number in the form INV-YYYYMMDD-0001, restarting every day within the outlet.
     */
    private function nextInvoiceNumber(Team $outlet): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $sequence = $outlet->sales()->where('invoice_number', 'like', "{$prefix}%")->count() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
