<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreProductCategoryRequest;
use App\Http\Requests\MasterData\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function __construct(private ProductCategoryRepository $categories) {}

    public function index(Request $request): Response
    {
        Gate::authorize('categories.view');

        return Inertia::render('master-data/categories/index', [
            'rows' => $this->categories->all($request->user()->currentTeam)
                ->map(fn (ProductCategory $category) => $this->row($category)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('categories.create');

        return Inertia::render('master-data/categories/form', ['record' => null]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('master-data.categories.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('categories.update');

        return Inertia::render('master-data/categories/form', [
            'record' => $this->row($this->categories->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->categories->update($this->categories->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('master-data.categories.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('categories.delete');

        $this->categories->delete($this->categories->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return to_route('master-data.categories.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'createdAt' => $category->created_at?->toDateString(),
        ];
    }
}
