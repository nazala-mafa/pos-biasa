<?php

namespace App\Repositories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Team;

class ReportRepository
{
    /**
     * @return array<int, array{id: int, date: string, invoice: string, customer: string|null, cashier: string|null, paymentMethod: string|null, itemsCount: int, subtotal: float, discount: float, tax: float, total: float}>
     */
    public function sales(Team $outlet): array
    {
        return $outlet->sales()
            ->with(['customer:id,name', 'user:id,name'])
            ->withCount('items')
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'date' => $sale->created_at->toDateString(),
                'invoice' => $sale->invoice_number,
                'customer' => $sale->customer?->name,
                'cashier' => $sale->user?->name,
                'paymentMethod' => $sale->payment_method,
                'itemsCount' => $sale->items_count,
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount,
                'tax' => (float) $sale->tax_total,
                'total' => (float) $sale->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, date: string, number: string, supplier: string|null, status: string, statusLabel: string, itemsCount: int, total: float}>
     */
    public function purchases(Team $outlet): array
    {
        return $outlet->purchaseOrders()
            ->with('supplier:id,name')
            ->withCount('items')
            ->latest('ordered_at')
            ->latest('id')
            ->get()
            ->map(fn (PurchaseOrder $order) => [
                'id' => $order->id,
                'date' => $order->ordered_at->toDateString(),
                'number' => $order->number,
                'supplier' => $order->supplier?->name,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'itemsCount' => $order->items_count,
                'total' => (float) $order->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, sku: string, name: string, category: string|null, unit: string|null, stock: float, minStock: float, costPrice: float, stockValue: float, isLow: bool}>
     */
    public function stock(Team $outlet): array
    {
        return $outlet->products()
            ->with(['unit:id,abbreviation', 'productCategory:id,name'])
            ->withSum('stockMovements as stock', 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $stock = (float) ($product->stock ?? 0);

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->productCategory?->name,
                    'unit' => $product->unit?->abbreviation,
                    'stock' => $stock,
                    'minStock' => (float) $product->min_stock,
                    'costPrice' => (float) $product->cost_price,
                    'stockValue' => round($stock * (float) $product->cost_price, 2),
                    'isLow' => $stock <= (float) $product->min_stock,
                ];
            })
            ->all();
    }

    /**
     * Cash ledger: sales are money in, returns and non-cancelled purchases are money out.
     *
     * @return array<int, array{id: string, date: string, type: string, reference: string, party: string|null, in: float, out: float}>
     */
    public function finance(Team $outlet): array
    {
        $sales = $outlet->sales()->with('customer:id,name')->get()->map(fn (Sale $sale) => [
            'id' => "sale-{$sale->id}",
            'date' => $sale->created_at->toDateString(),
            'type' => 'sale',
            'reference' => $sale->invoice_number,
            'party' => $sale->customer?->name,
            'in' => (float) $sale->total,
            'out' => 0.0,
        ]);

        $returns = SaleReturn::query()->where('team_id', $outlet->id)->with('sale.customer:id,name')->get()->map(fn (SaleReturn $return) => [
            'id' => "return-{$return->id}",
            'date' => $return->created_at->toDateString(),
            'type' => 'return',
            'reference' => $return->number,
            'party' => $return->sale?->customer?->name,
            'in' => 0.0,
            'out' => (float) $return->total,
        ]);

        $purchases = $outlet->purchaseOrders()
            ->with('supplier:id,name')
            ->where('status', '!=', PurchaseOrderStatus::Cancelled)
            ->get()
            ->map(fn (PurchaseOrder $order) => [
                'id' => "purchase-{$order->id}",
                'date' => $order->ordered_at->toDateString(),
                'type' => 'purchase',
                'reference' => $order->number,
                'party' => $order->supplier?->name,
                'in' => 0.0,
                'out' => (float) $order->total,
            ]);

        return $sales->concat($returns)->concat($purchases)
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * Daily profit and loss. Revenue is sales net of discount and tax; returns reduce revenue
     * and cost of goods is the cost price of the products sold minus the ones returned.
     *
     * @return array<int, array{date: string, revenue: float, returns: float, cost: float, profit: float}>
     */
    public function profitLoss(Team $outlet): array
    {
        $revenue = $returns = $cost = [];

        foreach ($outlet->sales()->get(['id', 'created_at', 'total', 'tax_total']) as $sale) {
            $this->accumulate($revenue, $sale->created_at->toDateString(), (float) $sale->total - (float) $sale->tax_total);
        }

        $saleItems = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->where('team_id', $outlet->id))
            ->with(['sale:id,created_at', 'product:id,cost_price'])
            ->get();

        foreach ($saleItems as $item) {
            $this->accumulate($cost, $item->sale->created_at->toDateString(), (float) $item->quantity * (float) ($item->product->cost_price ?? 0));
        }

        foreach (SaleReturn::query()->where('team_id', $outlet->id)->get(['id', 'created_at', 'total']) as $return) {
            $this->accumulate($returns, $return->created_at->toDateString(), (float) $return->total);
        }

        $returnItems = SaleReturnItem::query()
            ->whereHas('saleReturn', fn ($query) => $query->where('team_id', $outlet->id))
            ->with(['saleReturn:id,created_at', 'product:id,cost_price'])
            ->get();

        foreach ($returnItems as $item) {
            $this->accumulate($cost, $item->saleReturn->created_at->toDateString(), -(float) $item->quantity * (float) ($item->product->cost_price ?? 0));
        }

        $dates = array_values(array_unique([...array_keys($revenue), ...array_keys($returns), ...array_keys($cost)]));
        rsort($dates);

        return array_map(function (string $date) use ($revenue, $returns, $cost) {
            $dayRevenue = round($revenue[$date] ?? 0.0, 2);
            $dayReturns = round($returns[$date] ?? 0.0, 2);
            $dayCost = round($cost[$date] ?? 0.0, 2);

            return [
                'date' => $date,
                'revenue' => $dayRevenue,
                'returns' => $dayReturns,
                'cost' => $dayCost,
                'profit' => round($dayRevenue - $dayReturns - $dayCost, 2),
            ];
        }, $dates);
    }

    /**
     * @param  array<string, float>  $totals
     */
    private function accumulate(array &$totals, string $date, float $amount): void
    {
        $totals[$date] = ($totals[$date] ?? 0.0) + $amount;
    }
}
