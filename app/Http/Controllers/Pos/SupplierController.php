<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(private SupplierRepository $suppliers) {}

    public function index(Request $request): Response
    {
        Gate::authorize('suppliers.view');

        return Inertia::render('suppliers/index', [
            'rows' => $this->suppliers->all($request->user()->currentTeam)
                ->map(fn (Supplier $supplier) => $this->row($supplier)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('suppliers.create');

        return Inertia::render('suppliers/form', ['record' => null]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->suppliers->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier created.')]);

        return to_route('suppliers.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('suppliers.update');

        return Inertia::render('suppliers/form', [
            'record' => $this->row($this->suppliers->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdateSupplierRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->suppliers->update($this->suppliers->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier updated.')]);

        return to_route('suppliers.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('suppliers.delete');

        $this->suppliers->delete($this->suppliers->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier deleted.')]);

        return to_route('suppliers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contactPerson' => $supplier->contact_person,
            'contact_person' => $supplier->contact_person,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'createdAt' => $supplier->created_at?->toDateString(),
        ];
    }
}
