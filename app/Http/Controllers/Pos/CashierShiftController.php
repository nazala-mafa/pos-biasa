<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\CloseShiftRequest;
use App\Http\Requests\Shift\OpenShiftRequest;
use App\Models\CashierShift;
use App\Repositories\CashierShiftRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CashierShiftController extends Controller
{
    public function __construct(private CashierShiftRepository $shifts) {}

    public function index(Request $request): Response
    {
        Gate::authorize('shifts.view');

        $outlet = $request->user()->currentTeam;

        return Inertia::render('shifts/index', [
            'rows' => $this->shifts->history($outlet)->map(fn (CashierShift $shift) => $this->row($shift)),
            'currentShiftId' => $this->shifts->current($outlet, $request->user())?->id,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('shifts.open');

        return Inertia::render('shifts/form', [
            'currentShiftId' => $this->shifts->current($request->user()->currentTeam, $request->user())?->id,
        ]);
    }

    public function store(OpenShiftRequest $request): RedirectResponse
    {
        $shift = $this->shifts->open(
            $request->user()->currentTeam,
            $request->user(),
            (float) $request->validated('opening_cash'),
            $request->validated('note'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift opened.')]);

        return to_route('shifts.show', $shift->id);
    }

    public function show(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('shifts.view');

        $shift = $this->shifts->findOrFail($request->user()->currentTeam, $id);

        return Inertia::render('shifts/show', [
            'shift' => [
                ...$this->row($shift),
                'note' => $shift->note,
                'cashSales' => $this->shifts->cashSales($shift),
                'salesTotal' => (float) $shift->sales()->sum('total'),
                'salesCount' => $shift->sales()->count(),
                'liveExpectedCash' => $this->shifts->expectedCash($shift),
                'canClose' => $shift->closed_at === null
                    && $shift->user_id === $request->user()->id
                    && $request->user()->can('shifts.close'),
            ],
        ]);
    }

    public function close(CloseShiftRequest $request, string $currentTeam, int $id): RedirectResponse
    {
        $shift = $this->shifts->findOrFail($request->user()->currentTeam, $id);

        abort_unless($shift->user_id === $request->user()->id, 403);

        $this->shifts->close($shift, (float) $request->validated('closing_cash'), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift closed.')]);

        return to_route('shifts.show', $shift->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(CashierShift $shift): array
    {
        return [
            'id' => $shift->id,
            'user' => $shift->user->name,
            'isOpen' => $shift->closed_at === null,
            'openedAt' => $shift->opened_at->format('Y-m-d H:i'),
            'closedAt' => $shift->closed_at?->format('Y-m-d H:i'),
            'openedDate' => $shift->opened_at->toDateString(),
            'openingCash' => (float) $shift->opening_cash,
            'expectedCash' => $shift->expected_cash === null ? null : (float) $shift->expected_cash,
            'closingCash' => $shift->closing_cash === null ? null : (float) $shift->closing_cash,
            'difference' => $shift->closing_cash === null ? null : round((float) $shift->closing_cash - (float) $shift->expected_cash, 2),
        ];
    }
}
