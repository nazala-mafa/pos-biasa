<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreTaxRequest;
use App\Http\Requests\MasterData\UpdateTaxRequest;
use App\Models\Tax;
use App\Repositories\TaxRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function __construct(private TaxRepository $taxes) {}

    public function index(Request $request): Response
    {
        Gate::authorize('taxes.view');

        return Inertia::render('master-data/taxes/index', [
            'rows' => $this->taxes->all($request->user()->currentTeam)
                ->map(fn (Tax $tax) => $this->row($tax)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('taxes.create');

        return Inertia::render('master-data/taxes/form', ['record' => null]);
    }

    public function store(StoreTaxRequest $request): RedirectResponse
    {
        $this->taxes->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax created.')]);

        return to_route('master-data.taxes.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('taxes.update');

        return Inertia::render('master-data/taxes/form', [
            'record' => $this->row($this->taxes->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdateTaxRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->taxes->update($this->taxes->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax updated.')]);

        return to_route('master-data.taxes.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('taxes.delete');

        $this->taxes->delete($this->taxes->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax deleted.')]);

        return to_route('master-data.taxes.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Tax $tax): array
    {
        return [
            'id' => $tax->id,
            'name' => $tax->name,
            'rate' => (float) $tax->rate,
            'createdAt' => $tax->created_at?->toDateString(),
        ];
    }
}
