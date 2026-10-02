<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index', 'movements']),
            new Middleware('permission:inventory.adjust', only: ['create', 'store']),
        ];
    }

    /**
     * Current stock level of every product in the outlet.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('inventory/index', [
            'rows' => $this->outlet($request)->products()
                ->with('unit:id,abbreviation')
                ->withSum('stockMovements as stock', 'quantity')
                ->orderBy('name')
                ->get()
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
        return Inertia::render('inventory/movements', [
            'rows' => $this->outlet($request)->stockMovements()
                ->with(['product:id,sku,name', 'supplier:id,name', 'user:id,name'])
                ->latest()
                ->latest('id')
                ->get()
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
        $outlet = $this->outlet($request);

        return Inertia::render('inventory/form', [
            'products' => $outlet->products()
                ->where('is_active', true)
                ->with('unit:id,abbreviation')
                ->withSum('stockMovements as stock', 'quantity')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'label' => "{$product->sku} - {$product->name}",
                    'unit' => $product->unit?->abbreviation,
                    'stock' => (float) ($product->stock ?? 0),
                ]),
            'suppliers' => $outlet->suppliers()->orderBy('name')->get(['id', 'name']),
            'types' => collect(StockMovementType::cases())
                ->map(fn (StockMovementType $type) => ['value' => $type->value, 'label' => $type->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $outlet = $this->outlet($request);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $outlet->id)],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', 'not_in:0', 'between:-9999999999999,9999999999999'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('team_id', $outlet->id)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $type = StockMovementType::from($data['type']);
        $quantity = (float) $data['quantity'];

        if ($type !== StockMovementType::Adjustment && $quantity < 0) {
            throw ValidationException::withMessages(['quantity' => __('The quantity must be greater than 0.')]);
        }

        $delta = $type === StockMovementType::Out ? -$quantity : $quantity;
        $stock = (float) $outlet->stockMovements()->where('product_id', $data['product_id'])->sum('quantity');

        if ($stock + $delta < 0) {
            throw ValidationException::withMessages(['quantity' => __('Insufficient stock. Current stock: :stock.', ['stock' => $stock])]);
        }

        $outlet->stockMovements()->create([
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

    private function outlet(Request $request): Team
    {
        return $request->user()->currentTeam;
    }
}
