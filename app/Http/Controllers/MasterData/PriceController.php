<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StorePriceRequest;
use App\Http\Requests\MasterData\UpdatePriceRequest;
use App\Models\Price;
use App\Repositories\PriceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PriceController extends Controller
{
    public function __construct(private PriceRepository $prices) {}

    public function index(Request $request): Response
    {
        Gate::authorize('prices.view');

        return Inertia::render('master-data/prices/index', [
            'rows' => $this->prices->all($request->user()->currentTeam)
                ->map(fn (Price $price) => $this->row($price)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('prices.create');

        return Inertia::render('master-data/prices/form', ['record' => null]);
    }

    public function store(StorePriceRequest $request): RedirectResponse
    {
        $this->prices->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price created.')]);

        return to_route('master-data.prices.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('prices.update');

        return Inertia::render('master-data/prices/form', [
            'record' => $this->row($this->prices->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdatePriceRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->prices->update($this->prices->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price updated.')]);

        return to_route('master-data.prices.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('prices.delete');

        $this->prices->delete($this->prices->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price deleted.')]);

        return to_route('master-data.prices.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Price $price): array
    {
        return [
            'id' => $price->id,
            'name' => $price->name,
            'description' => $price->description,
            'createdAt' => $price->created_at?->toDateString(),
        ];
    }
}
