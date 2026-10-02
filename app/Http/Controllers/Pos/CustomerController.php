<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerRepository $customers) {}

    public function index(Request $request): Response
    {
        Gate::authorize('customers.view');

        return Inertia::render('customers/index', [
            'rows' => $this->customers->all($request->user()->currentTeam)
                ->map(fn (Customer $customer) => $this->row($customer)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('customers.create');

        return Inertia::render('customers/form', ['record' => null]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->customers->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer created.')]);

        return to_route('customers.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('customers.update');

        return Inertia::render('customers/form', [
            'record' => $this->row($this->customers->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdateCustomerRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->customers->update($this->customers->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer updated.')]);

        return to_route('customers.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('customers.delete');

        $this->customers->delete($this->customers->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer deleted.')]);

        return to_route('customers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'createdAt' => $customer->created_at?->toDateString(),
        ];
    }
}
