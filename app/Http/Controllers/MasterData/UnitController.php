<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreUnitRequest;
use App\Http\Requests\MasterData\UpdateUnitRequest;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct(private UnitRepository $units) {}

    public function index(Request $request): Response
    {
        Gate::authorize('units.view');

        return Inertia::render('master-data/units/index', [
            'rows' => $this->units->all($request->user()->currentTeam)
                ->map(fn (Unit $unit) => $this->row($unit)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('units.create');

        return Inertia::render('master-data/units/form', ['record' => null]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->units->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return to_route('master-data.units.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('units.update');

        return Inertia::render('master-data/units/form', [
            'record' => $this->row($this->units->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdateUnitRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->units->update($this->units->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return to_route('master-data.units.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('units.delete');

        $this->units->delete($this->units->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return to_route('master-data.units.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'abbreviation' => $unit->abbreviation,
            'createdAt' => $unit->created_at?->toDateString(),
        ];
    }
}
