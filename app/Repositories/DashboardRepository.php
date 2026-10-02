<?php

namespace App\Repositories;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use stdClass;

class DashboardRepository
{
    public const PERIODS = ['today', '7d', '30d'];

    /**
     * Everything the dashboard shows for one outlet over a period.
     *
     * @param  'today'|'7d'|'30d'  $period
     * @return array<string, mixed>
     */
    public function summary(Team $outlet, string $period): array
    {
        $days = $this->days($period);
        $start = now()->startOfDay()->subDays($days - 1);
        $chartDays = max($days, 7);
        $chartStart = now()->startOfDay()->subDays($chartDays - 1);

        return [
            'period' => $period,
            'generatedAt' => now()->toIso8601String(),
            'kpis' => $this->kpis($outlet, $start),
            'chart' => $this->chart($outlet, $chartStart, $chartDays),
            'shifts' => $this->openShifts($outlet),
            'recentSales' => $this->recentSales($outlet),
            'topProducts' => $this->topProducts($outlet, $start),
            'lowStock' => $this->lowStock($outlet),
            'activities' => $this->activities($outlet),
        ];
    }

    private function days(string $period): int
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            default => 1,
        };
    }

    /**
     * Receivables are the unpaid part of invoices (total above what was paid).
     *
     * @return array{sales: float, transactions: int, cashIn: float, receivables: float}
     */
    private function kpis(Team $outlet, CarbonInterface $start): array
    {
        $row = $outlet->sales()
            ->where('created_at', '>=', $start)
            ->toBase()
            ->selectRaw('COALESCE(SUM(total), 0) as sales, COUNT(*) as transactions, COALESCE(SUM(paid - `change`), 0) as cash_in, COALESCE(SUM(CASE WHEN total > paid THEN total - paid ELSE 0 END), 0) as receivables')
            ->first();

        return [
            'sales' => (float) $row->sales,
            'transactions' => (int) $row->transactions,
            'cashIn' => (float) $row->cash_in,
            'receivables' => (float) $row->receivables,
        ];
    }

    /**
     * @return array<int, array{date: string, label: string, total: float, transactions: int}>
     */
    private function chart(Team $outlet, CarbonInterface $start, int $days): array
    {
        $byDate = $outlet->sales()
            ->where('created_at', '>=', $start)
            ->get(['total', 'created_at'])
            ->groupBy(fn (Sale $sale) => $sale->created_at->toDateString());

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start, $byDate) {
                $date = $start->addDays($offset);
                $sales = $byDate->get($date->toDateString(), collect());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->translatedFormat('D, j M'),
                    'total' => (float) $sales->sum('total'),
                    'transactions' => $sales->count(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: int, cashier: string|null, openedAt: string, openingCash: float, salesTotal: float, salesCount: int}>
     */
    private function openShifts(Team $outlet): array
    {
        return $outlet->cashierShifts()
            ->whereNull('closed_at')
            ->with('user:id,name')
            ->withSum('sales as sales_total', 'total')
            ->withCount('sales')
            ->orderBy('opened_at')
            ->get()
            ->map(fn (CashierShift $shift) => [
                'id' => $shift->id,
                'cashier' => $shift->user?->name,
                'openedAt' => $shift->opened_at->toIso8601String(),
                'openingCash' => (float) $shift->opening_cash,
                'salesTotal' => (float) ($shift->sales_total ?? 0),
                'salesCount' => $shift->sales_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, invoice: string, customer: string|null, cashier: string|null, total: float, createdAt: string}>
     */
    private function recentSales(Team $outlet): array
    {
        return $outlet->sales()
            ->with(['customer:id,name', 'user:id,name'])
            ->latest()
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'customer' => $sale->customer?->name,
                'cashier' => $sale->user?->name,
                'total' => (float) $sale->total,
                'createdAt' => $sale->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{productId: int|null, name: string, quantity: float, revenue: float}>
     */
    private function topProducts(Team $outlet, CarbonInterface $start): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.team_id', $outlet->id)
            ->where('sales.created_at', '>=', $start)
            ->groupBy('sale_items.product_id', 'sale_items.name')
            ->toBase()
            ->selectRaw('sale_items.product_id, sale_items.name, SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal) as revenue')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn (stdClass $item) => [
                'productId' => $item->product_id !== null ? (int) $item->product_id : null,
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'revenue' => (float) $item->revenue,
            ])
            ->all();
    }

    /**
     * Active products at or under their minimum stock, emptiest first.
     *
     * @return array{count: int, items: array<int, array{id: int, name: string, sku: string, stock: float, minStock: float, unit: string|null}>}
     */
    private function lowStock(Team $outlet): array
    {
        $low = $outlet->products()
            ->where('is_active', true)
            ->with('unit:id,abbreviation')
            ->withSum('stockMovements as stock', 'quantity')
            ->get()
            ->filter(fn (Product $product) => (float) ($product->stock ?? 0) <= (float) $product->min_stock)
            ->sortBy(fn (Product $product) => (float) ($product->stock ?? 0))
            ->values();

        return [
            'count' => $low->count(),
            'items' => $low->take(6)->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => (float) ($product->stock ?? 0),
                'minStock' => (float) $product->min_stock,
                'unit' => $product->unit?->abbreviation,
            ])->all(),
        ];
    }

    /**
     * The latest shift, return, purchase and stock events merged into one timeline.
     *
     * @return array<int, array{type: string, title: string, detail: string|null, at: string}>
     */
    private function activities(Team $outlet): array
    {
        /** @var Collection<int, array{type: string, title: string, detail: string|null, at: CarbonInterface}> $events */
        $events = collect();

        $outlet->cashierShifts()->with('user:id,name')->latest('opened_at')->limit(5)->get()
            ->each(function (CashierShift $shift) use ($events) {
                $events->push(['type' => 'shift', 'title' => Lang::string(':user opened a shift', ['user' => $shift->user->name ?? '-']), 'detail' => null, 'at' => $shift->opened_at]);

                if ($shift->closed_at !== null) {
                    $events->push(['type' => 'shift', 'title' => Lang::string(':user closed a shift', ['user' => $shift->user->name ?? '-']), 'detail' => null, 'at' => $shift->closed_at]);
                }
            });

        $outlet->saleReturns()->with('sale:id,invoice_number')->latest()->limit(5)->get()
            ->each(fn (SaleReturn $return) => $events->push(['type' => 'return', 'title' => Lang::string('Return :number', ['number' => $return->number]), 'detail' => $return->sale?->invoice_number, 'at' => $return->created_at]));

        $outlet->purchaseOrders()->with('supplier:id,name')->latest()->limit(5)->get()
            ->each(fn (PurchaseOrder $order) => $events->push(['type' => 'purchase', 'title' => Lang::string('Purchase order :number', ['number' => $order->number]), 'detail' => $order->supplier?->name, 'at' => $order->created_at]));

        $outlet->stockMovements()->with('product:id,name')->where('type', '!=', 'out')->latest()->limit(5)->get()
            ->each(fn (StockMovement $movement) => $events->push(['type' => 'stock', 'title' => $movement->type->label().': '.($movement->product->name ?? '-'), 'detail' => $movement->quantity > 0 ? '+'.(float) $movement->quantity : (string) (float) $movement->quantity, 'at' => $movement->created_at]));

        return $events
            ->sortByDesc('at')
            ->take(8)
            ->map(fn (array $event) => [...$event, 'at' => $event['at']->toIso8601String()])
            ->values()
            ->all();
    }
}
