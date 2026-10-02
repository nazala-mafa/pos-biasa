<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleReturn\StoreSaleReturnRequest;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Repositories\SaleRepository;
use App\Repositories\SaleReturnRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function __construct(
        private SaleReturnRepository $returns,
        private SaleRepository $sales,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('returns.view');

        return Inertia::render('returns/index', [
            'rows' => $this->returns->history($request->user()->currentTeam)
                ->map(fn (SaleReturn $return) => [
                    'id' => $return->id,
                    'number' => $return->number,
                    'invoiceNumber' => $return->sale->invoice_number,
                    'user' => $return->user?->name,
                    'reason' => $return->reason,
                    'itemsCount' => $return->items_count,
                    'total' => (float) $return->total,
                    'createdAt' => $return->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * Pick a sale (or arrive from its detail page with ?sale_id=) and enter what comes back.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('returns.create');

        $outlet = $request->user()->currentTeam;
        $selected = $request->integer('sale_id') ?: null;
        $sale = $selected ? $this->sales->findOrFail($outlet, $selected) : null;

        return Inertia::render('returns/form', [
            'sales' => $this->sales->history($outlet)->take(200)
                ->map(fn ($row) => ['id' => $row->id, 'label' => "{$row->invoice_number} - {$row->created_at?->toDateString()}"])
                ->values(),
            'sale' => $sale === null ? null : [
                'id' => $sale->id,
                'invoiceNumber' => $sale->invoice_number,
                'lines' => $this->returns->returnableLines($sale)->map(fn (array $line) => [
                    'id' => $line['item']->id,
                    'sku' => $line['item']->sku,
                    'name' => $line['item']->name,
                    'sold' => (float) $line['item']->quantity,
                    'returned' => $line['returned'],
                    'returnable' => $line['returnable'],
                    'unitPrice' => (float) $line['item']->unit_price,
                ])->values(),
            ],
        ]);
    }

    public function store(StoreSaleReturnRequest $request): RedirectResponse
    {
        $outlet = $request->user()->currentTeam;
        $sale = $this->sales->findOrFail($outlet, (int) $request->validated('sale_id'));

        $return = $this->returns->create($outlet, $request->user(), $sale, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Return recorded.')]);

        return to_route('returns.show', $return->id);
    }

    public function show(Request $request, string $currentTeam, int $id): Response
    {
        Gate::authorize('returns.view');

        $return = $this->returns->findOrFail($request->user()->currentTeam, $id);

        return Inertia::render('returns/show', [
            'saleReturn' => [
                'id' => $return->id,
                'number' => $return->number,
                'saleId' => $return->sale_id,
                'invoiceNumber' => $return->sale->invoice_number,
                'user' => $return->user?->name,
                'reason' => $return->reason,
                'total' => (float) $return->total,
                'createdAt' => $return->created_at?->format('Y-m-d H:i'),
                'items' => $return->items->map(fn (SaleReturnItem $item) => [
                    'id' => $item->id,
                    'sku' => $item->saleItem->sku,
                    'name' => $item->saleItem->name,
                    'quantity' => (float) $item->quantity,
                    'amount' => (float) $item->amount,
                ]),
            ],
        ]);
    }
}
