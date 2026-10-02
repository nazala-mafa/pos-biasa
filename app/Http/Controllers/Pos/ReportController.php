<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Repositories\ReportRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportRepository $reports) {}

    public function sales(Request $request): Response
    {
        Gate::authorize('reports.sales');

        return Inertia::render('reports/sales', ['rows' => $this->reports->sales($request->user()->currentTeam)]);
    }

    public function purchases(Request $request): Response
    {
        Gate::authorize('reports.purchases');

        return Inertia::render('reports/purchases', ['rows' => $this->reports->purchases($request->user()->currentTeam)]);
    }

    public function stock(Request $request): Response
    {
        Gate::authorize('reports.stock');

        return Inertia::render('reports/stock', ['rows' => $this->reports->stock($request->user()->currentTeam)]);
    }

    public function finance(Request $request): Response
    {
        Gate::authorize('reports.finance');

        return Inertia::render('reports/finance', ['rows' => $this->reports->finance($request->user()->currentTeam)]);
    }

    public function profitLoss(Request $request): Response
    {
        Gate::authorize('reports.profit-loss');

        return Inertia::render('reports/profit-loss', ['rows' => $this->reports->profitLoss($request->user()->currentTeam)]);
    }
}
