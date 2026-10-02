<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockOpname\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Repositories\ProductRepository;
use App\Repositories\StockOpnameRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(
        private StockOpnameRepository $opnames,
        private ProductRepository $products,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('stock-opnames.view');

        return Inertia::render('stock-opnames/index', [
            'rows' => $this->opnames->history($request->user()->currentTeam)
                ->map(fn (StockOpname $opname) => [
                    'id' => $opname->id,
                    'number' => $opname->number,
                    'user' => $opname->user?->name,
                    'itemsCount' => $opname->items_count,
                    'differenceTotal' => (float) ($opname->difference_total ?? 0),
                    'note' => $opname->note,
                    'countedAt' => $opname->counted_at->toDateString(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('stock-opnames.create');

        return Inertia::render('stock-opnames/form', [
            'products' => $this->products->withStock($request->user()->currentTeam, activeOnly: true)
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit?->abbreviation,
                    'stock' => (float) ($product->stock ?? 0),
                ])
                ->values(),
        ]);
    }

    public function store(StoreStockOpnameRequest $request): RedirectResponse
    {
        $opname = $this->opnames->create($request->user()->currentTeam, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock opname recorded.')]);

        return to_route('stock-opnames.show', $opname->id);
    }

    public function show(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('stock-opnames.view');

        $opname = $this->opnames->findOrFail($request->user()->currentTeam, $id);

        return Inertia::render('stock-opnames/show', [
            'opname' => [
                'id' => $opname->id,
                'number' => $opname->number,
                'user' => $opname->user?->name,
                'note' => $opname->note,
                'countedAt' => $opname->counted_at->toDateString(),
                'items' => $opname->items->map(fn (StockOpnameItem $item) => [
                    'id' => $item->id,
                    'sku' => $item->product->sku,
                    'name' => $item->product->name,
                    'unit' => $item->product->unit?->abbreviation,
                    'systemQuantity' => (float) $item->system_quantity,
                    'countedQuantity' => (float) $item->counted_quantity,
                    'difference' => (float) $item->difference,
                ]),
            ],
        ]);
    }
}
