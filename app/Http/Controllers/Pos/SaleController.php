<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(
        private SaleRepository $sales,
        private ProductRepository $products,
        private CustomerRepository $customers,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('sales.view');

        return Inertia::render('sales/index', [
            'rows' => $this->sales->history($request->user()->currentTeam)
                ->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'invoiceNumber' => $sale->invoice_number,
                    'customer' => $sale->customer?->name,
                    'user' => $sale->user?->name,
                    'paymentLabel' => $sale->payment_method,
                    'itemsCount' => $sale->items_count,
                    'total' => (float) $sale->total,
                    'createdAt' => $sale->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * The cashier screen: sellable products with their stock plus the checkout options.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('sales.create');

        $outlet = $request->user()->currentTeam;

        return Inertia::render('sales/create', [
            'products' => $this->products->withStock($outlet, activeOnly: true)
                ->load('productCategory:id,name', 'tax:id,rate')
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->productCategory?->name,
                    'unit' => $product->unit?->abbreviation,
                    'photoUrl' => $product->photoUrl(),
                    'price' => (float) $product->selling_price,
                    'taxRate' => (float) ($product->tax->rate ?? 0),
                    'stock' => (float) ($product->stock ?? 0),
                ]),
            'customers' => $this->customers->all($outlet)->map->only(['id', 'name'])->values(),
            'paymentMethods' => $outlet->paymentMethods()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (PaymentMethod $method) => ['value' => (string) $method->id, 'label' => $method->name]),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $sale = $this->sales->checkout($request->user()->currentTeam, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale recorded.')]);

        return to_route('sales.show', $sale->id);
    }

    public function show(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('sales.view');

        $sale = $this->sales->findOrFail($request->user()->currentTeam, $id);

        return Inertia::render('sales/show', [
            'sale' => [
                'id' => $sale->id,
                'invoiceNumber' => $sale->invoice_number,
                'customer' => $sale->customer?->name,
                'user' => $sale->user?->name,
                'paymentLabel' => $sale->payment_method,
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount,
                'taxTotal' => (float) $sale->tax_total,
                'total' => (float) $sale->total,
                'paid' => (float) $sale->paid,
                'change' => (float) $sale->change,
                'note' => $sale->note,
                'createdAt' => $sale->created_at?->format('Y-m-d H:i'),
                'items' => $sale->items->map(fn (SaleItem $item) => [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'unitPrice' => (float) $item->unit_price,
                    'taxAmount' => (float) $item->tax_amount,
                    'subtotal' => (float) $item->subtotal,
                ]),
            ],
        ]);
    }
}
