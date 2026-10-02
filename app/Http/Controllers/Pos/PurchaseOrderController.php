<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\SupplierRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private ProductRepository $products,
        private SupplierRepository $suppliers,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('purchases.view');

        return Inertia::render('purchases/index', [
            'rows' => $this->purchaseOrders->history($request->user()->currentTeam)
                ->map(fn (PurchaseOrder $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'supplier' => $order->supplier->name,
                    'user' => $order->user?->name,
                    'status' => $order->status->value,
                    'statusLabel' => $order->status->label(),
                    'itemsCount' => $order->items_count,
                    'total' => (float) $order->total,
                    'orderedAt' => $order->ordered_at->toDateString(),
                    'expectedAt' => $order->expected_at?->toDateString(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('purchases.create');

        $outlet = $request->user()->currentTeam;

        return Inertia::render('purchases/form', [
            'suppliers' => $this->suppliers->all($outlet)->map->only(['id', 'name'])->values(),
            'products' => $this->products->all($outlet)
                ->where('is_active', true)
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'label' => "{$product->sku} - {$product->name}",
                    'unit' => $product->unit?->abbreviation,
                    'costPrice' => (float) $product->cost_price,
                ])
                ->values(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->purchaseOrders->create($request->user()->currentTeam, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order created.')]);

        return to_route('purchases.show', $order->id);
    }

    public function show(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('purchases.view');

        $order = $this->purchaseOrders->findOrFail($request->user()->currentTeam, $id);

        return Inertia::render('purchases/show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'supplier' => $order->supplier->name,
                'user' => $order->user?->name,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'isReceivable' => $order->status->isReceivable(),
                'canCancel' => $order->status === PurchaseOrderStatus::Ordered,
                'total' => (float) $order->total,
                'orderedAt' => $order->ordered_at->toDateString(),
                'expectedAt' => $order->expected_at?->toDateString(),
                'note' => $order->note,
                'items' => $order->items->map(fn (PurchaseOrderItem $item) => [
                    'id' => $item->id,
                    'sku' => $item->product->sku,
                    'name' => $item->product->name,
                    'quantity' => (float) $item->quantity,
                    'receivedQuantity' => (float) $item->received_quantity,
                    'unitCost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'receipts' => $order->receipts->map(fn (GoodsReceipt $receipt) => [
                    'id' => $receipt->id,
                    'number' => $receipt->number,
                    'receivedAt' => $receipt->received_at->toDateString(),
                    'user' => $receipt->user?->name,
                    'note' => $receipt->note,
                    'quantity' => (float) $receipt->items->sum('quantity'),
                ]),
            ],
        ]);
    }

    public function receive(ReceivePurchaseOrderRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $outlet = $request->user()->currentTeam;

        $this->purchaseOrders->receive($outlet, $request->user(), $this->purchaseOrders->findOrFail($outlet, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goods received.')]);

        return to_route('purchases.show', $id);
    }

    public function cancel(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('purchases.cancel');

        $this->purchaseOrders->cancel($this->purchaseOrders->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order cancelled.')]);

        return to_route('purchases.show', $id);
    }
}
