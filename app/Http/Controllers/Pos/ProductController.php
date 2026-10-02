<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TaxRepository;
use App\Repositories\UnitRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $products,
        private ProductCategoryRepository $categories,
        private UnitRepository $units,
        private TaxRepository $taxes,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('products.view');

        return Inertia::render('products/index', [
            'rows' => $this->products->all($request->user()->currentTeam)
                ->map(fn (Product $product) => $this->row($product)),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('products.create');

        return Inertia::render('products/form', [
            'record' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->products->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('products.update');

        return Inertia::render('products/form', [
            'record' => $this->row($this->products->findOrFail($request->user()->currentTeam, $id)),
            ...$this->formOptions($request),
        ]);
    }

    public function update(UpdateProductRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->products->update($this->products->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('products.delete');

        $this->products->delete($this->products->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index');
    }

    /**
     * Select options for the product form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $outlet = $request->user()->currentTeam;

        return [
            'categories' => $this->categories->all($outlet)->map->only(['id', 'name'])->values(),
            'units' => $this->units->all($outlet)->map->only(['id', 'name', 'abbreviation'])->values(),
            'taxes' => $this->taxes->all($outlet)->map->only(['id', 'name', 'rate'])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'categoryId' => $product->product_category_id,
            'category' => $product->productCategory?->name,
            'unitId' => $product->unit_id,
            'unit' => $product->unit?->abbreviation,
            'taxId' => $product->tax_id,
            'tax' => $product->tax?->name,
            'costPrice' => (float) $product->cost_price,
            'sellingPrice' => (float) $product->selling_price,
            'minStock' => (float) $product->min_stock,
            'isActive' => $product->is_active,
            'createdAt' => $product->created_at?->toDateString(),
        ];
    }
}
