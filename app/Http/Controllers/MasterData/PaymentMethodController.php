<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StorePaymentMethodRequest;
use App\Http\Requests\MasterData\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Repositories\PaymentMethodRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private PaymentMethodRepository $paymentMethods) {}

    public function index(Request $request): Response
    {
        Gate::authorize('payment-methods.view');

        return Inertia::render('master-data/payment-methods/index', [
            'rows' => $this->paymentMethods->all($request->user()->currentTeam)
                ->map(fn (PaymentMethod $method) => $this->row($method)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('payment-methods.create');

        return Inertia::render('master-data/payment-methods/form', ['record' => null]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $this->paymentMethods->create($request->user()->currentTeam, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method created.')]);

        return to_route('master-data.payment-methods.index');
    }

    public function edit(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('payment-methods.update');

        return Inertia::render('master-data/payment-methods/form', [
            'record' => $this->row($this->paymentMethods->findOrFail($request->user()->currentTeam, $id)),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $this->paymentMethods->update($this->paymentMethods->findOrFail($request->user()->currentTeam, $id), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method updated.')]);

        return to_route('master-data.payment-methods.index');
    }

    public function destroy(Request $request, string $currentTeam, int $id): RedirectResponse
    {
        Gate::authorize('payment-methods.delete');

        $this->paymentMethods->delete($this->paymentMethods->findOrFail($request->user()->currentTeam, $id));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method deleted.')]);

        return to_route('master-data.payment-methods.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(PaymentMethod $method): array
    {
        return [
            'id' => $method->id,
            'name' => $method->name,
            'isCash' => $method->is_cash,
            'isActive' => $method->is_active,
            'createdAt' => $method->created_at?->toDateString(),
        ];
    }
}
