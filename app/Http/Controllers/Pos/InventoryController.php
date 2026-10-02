<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;

use App\Enums\StockMovementType;
use App\Http\Requests\Inventory\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use App\Repositories\SupplierRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private ProductRepository $products,
        private SupplierRepository $suppliers,
        private StockMovementRepository $stockMovements,
    ) {}

    /**
     * Current stock level of every product in the outlet.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('inventory/index', [
            'rows' => $this->products->withStock($request->user()->currentTeam)
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit?->abbreviation,
                    'stock' => (float) ($product->stock ?? 0),
                    'minStock' => (float) $product->min_stock,
                    'isActive' => $product->is_active,
                    'createdAt' => $product->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * History of every stock movement in the outlet.
     */
    public function movements(Request $request): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('inventory/movements', [
            'rows' => $this->stockMovements->history($request->user()->currentTeam)
                ->map(fn (StockMovement $movement) => [
                    'id' => $movement->id,
                    'product' => $movement->product->name,
                    'sku' => $movement->product->sku,
                    'type' => $movement->type->value,
                    'typeLabel' => $movement->type->label(),
                    'quantity' => (float) $movement->quantity,
                    'supplier' => $movement->supplier?->name,
                    'user' => $movement->user?->name,
                    'note' => $movement->note,
                    'createdAt' => $movement->created_at?->toDateString(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('inventory.adjust');

        $outlet = $request->user()->currentTeam;

        return Inertia::render('inventory/form', [
            'products' => $this->products->withStock($outlet, activeOnly: true)
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'label' => "{$product->sku} - {$product->name}",
                    'unit' => $product->unit?->abbreviation,
                    'stock' => (float) ($product->stock ?? 0),
                ]),
            'suppliers' => $this->suppliers->all($outlet)->map->only(['id', 'name'])->values(),
            'types' => collect(StockMovementType::cases())
                ->map(fn (StockMovementType $type) => ['value' => $type->value, 'label' => $type->label()]),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $outlet = $request->user()->currentTeam;
        $data = $request->validated();

        $type = StockMovementType::from($data['type']);
        $quantity = (float) $data['quantity'];

        if ($type !== StockMovementType::Adjustment && $quantity < 0) {
            throw ValidationException::withMessages(['quantity' => __('The quantity must be greater than 0.')]);
        }

        $delta = $type === StockMovementType::Out ? -$quantity : $quantity;
        $stock = $this->stockMovements->currentStock($outlet, $data['product_id']);

        if ($stock + $delta < 0) {
            throw ValidationException::withMessages(['quantity' => __('Insufficient stock. Current stock: :stock.', ['stock' => $stock])]);
        }

        $this->stockMovements->create($outlet, [
            'product_id' => $data['product_id'],
            'supplier_id' => $type === StockMovementType::In ? ($data['supplier_id'] ?? null) : null,
            'user_id' => $request->user()->id,
            'type' => $type,
            'quantity' => $delta,
            'note' => $data['note'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock movement recorded.')]);

        return to_route('inventory.index');
    }
}
