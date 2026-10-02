<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PaymentMethod;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Team;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exports every menu of an outlet as its own sheet. Passing no outlet yields an empty template.
 */
class OutletDataExport implements Export, WithMultipleSheets
{
    /**
     * Sheet name => column headings. Order matters: it is the order sheets are written and imported.
     *
     * @var array<string, array<int, string>>
     */
    public const SHEETS = [
        'categories' => ['name', 'description'],
        'units' => ['name', 'abbreviation'],
        'taxes' => ['name', 'rate'],
        'prices' => ['name', 'description'],
        'payment_methods' => ['name', 'is_cash', 'is_active'],
        'products' => ['sku', 'name', 'category', 'unit', 'tax', 'description', 'cost_price', 'selling_price', 'min_stock', 'is_active'],
        'customers' => ['name', 'phone', 'email', 'address'],
        'suppliers' => ['name', 'contact_person', 'phone', 'email', 'address'],
        'stock_movements' => ['date', 'product_sku', 'supplier', 'type', 'quantity', 'note'],
        'purchase_orders' => ['number', 'supplier', 'status', 'ordered_at', 'expected_at', 'note'],
        'purchase_order_items' => ['po_number', 'product_sku', 'quantity', 'received_quantity', 'unit_cost'],
        'goods_receipts' => ['number', 'po_number', 'received_at', 'note'],
        'goods_receipt_items' => ['receipt_number', 'product_sku', 'quantity'],
        'sales' => ['invoice_number', 'date', 'customer', 'payment_method', 'discount', 'paid', 'note'],
        'sale_items' => ['invoice_number', 'product_sku', 'quantity', 'unit_price'],
        'sale_returns' => ['number', 'invoice_number', 'date', 'reason'],
        'sale_return_items' => ['return_number', 'product_sku', 'quantity'],
        'stock_opnames' => ['number', 'counted_at', 'note'],
        'stock_opname_items' => ['opname_number', 'product_sku', 'system_quantity', 'counted_quantity'],
    ];

    public function __construct(private ?Team $outlet = null) {}

    /**
     * @return array<int, OutletDataSheetExport>
     */
    public function sheets(): array
    {
        return collect(self::SHEETS)
            ->map(fn (array $headings, string $sheet) => new OutletDataSheetExport($sheet, $headings, $this->rows($sheet)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<int, int|float|string|null>>
     */
    private function rows(string $sheet): array
    {
        if ($this->outlet === null) {
            return [];
        }

        return match ($sheet) {
            'categories' => $this->outlet->productCategories()->orderBy('name')->get()
                ->map(fn (ProductCategory $row) => [$row->name, $row->description])->all(),
            'units' => $this->outlet->units()->orderBy('name')->get()
                ->map(fn (Unit $row) => [$row->name, $row->abbreviation])->all(),
            'taxes' => $this->outlet->taxes()->orderBy('name')->get()
                ->map(fn (Tax $row) => [$row->name, (float) $row->rate])->all(),
            'prices' => $this->outlet->prices()->orderBy('name')->get()
                ->map(fn (Price $row) => [$row->name, $row->description])->all(),
            'payment_methods' => $this->outlet->paymentMethods()->orderBy('name')->get()
                ->map(fn (PaymentMethod $row) => [$row->name, $row->is_cash ? 1 : 0, $row->is_active ? 1 : 0])->all(),
            'products' => $this->outlet->products()->with(['productCategory', 'unit', 'tax'])->orderBy('sku')->get()
                ->map(fn (Product $row) => [
                    $row->sku, $row->name, $row->productCategory?->name, $row->unit?->name, $row->tax?->name,
                    $row->description, (float) $row->cost_price, (float) $row->selling_price, (float) $row->min_stock,
                    $row->is_active ? 1 : 0,
                ])->all(),
            'customers' => $this->outlet->customers()->orderBy('name')->get()
                ->map(fn (Customer $row) => [$row->name, $row->phone, $row->email, $row->address])->all(),
            'suppliers' => $this->outlet->suppliers()->orderBy('name')->get()
                ->map(fn (Supplier $row) => [$row->name, $row->contact_person, $row->phone, $row->email, $row->address])->all(),
            'stock_movements' => $this->outlet->stockMovements()->with(['product', 'supplier'])->orderBy('id')->get()
                ->map(fn (StockMovement $row) => [$row->created_at?->format('Y-m-d H:i:s'), $row->product?->sku, $row->supplier?->name, $row->type->value, (float) $row->quantity, $row->note])->all(),
            'purchase_orders' => $this->outlet->purchaseOrders()->with('supplier')->orderBy('number')->get()
                ->map(fn (PurchaseOrder $row) => [$row->number, $row->supplier?->name, $row->status->value, $row->ordered_at->toDateString(), $row->expected_at?->toDateString(), $row->note])->all(),
            'purchase_order_items' => PurchaseOrderItem::whereHas('purchaseOrder', fn ($query) => $query->where('team_id', $this->outlet->id))
                ->with(['purchaseOrder', 'product'])->orderBy('id')->get()
                ->map(fn (PurchaseOrderItem $row) => [$row->purchaseOrder->number, $row->product?->sku, (float) $row->quantity, (float) $row->received_quantity, (float) $row->unit_cost])->all(),
            'goods_receipts' => $this->outlet->goodsReceipts()->with('purchaseOrder')->orderBy('number')->get()
                ->map(fn (GoodsReceipt $row) => [$row->number, $row->purchaseOrder?->number, $row->received_at->toDateString(), $row->note])->all(),
            'goods_receipt_items' => GoodsReceiptItem::whereHas('goodsReceipt', fn ($query) => $query->where('team_id', $this->outlet->id))
                ->with(['goodsReceipt', 'product'])->orderBy('id')->get()
                ->map(fn (GoodsReceiptItem $row) => [$row->goodsReceipt->number, $row->product?->sku, (float) $row->quantity])->all(),
            'sales' => $this->outlet->sales()->with('customer')->orderBy('invoice_number')->get()
                ->map(fn (Sale $row) => [$row->invoice_number, $row->created_at?->format('Y-m-d H:i:s'), $row->customer?->name, $row->payment_method, (float) $row->discount, (float) $row->paid, $row->note])->all(),
            'sale_items' => SaleItem::whereHas('sale', fn ($query) => $query->where('team_id', $this->outlet->id))
                ->with('sale')->orderBy('id')->get()
                ->map(fn (SaleItem $row) => [$row->sale->invoice_number, $row->sku, (float) $row->quantity, (float) $row->unit_price])->all(),
            'sale_returns' => $this->outlet->saleReturns()->with('sale')->orderBy('number')->get()
                ->map(fn (SaleReturn $row) => [$row->number, $row->sale?->invoice_number, $row->created_at?->format('Y-m-d H:i:s'), $row->reason])->all(),
            'sale_return_items' => SaleReturnItem::whereHas('saleReturn', fn ($query) => $query->where('team_id', $this->outlet->id))
                ->with(['saleReturn', 'saleItem'])->orderBy('id')->get()
                ->map(fn (SaleReturnItem $row) => [$row->saleReturn->number, $row->saleItem?->sku, (float) $row->quantity])->all(),
            'stock_opnames' => $this->outlet->stockOpnames()->orderBy('number')->get()
                ->map(fn (StockOpname $row) => [$row->number, $row->counted_at->toDateString(), $row->note])->all(),
            'stock_opname_items' => StockOpnameItem::whereHas('stockOpname', fn ($query) => $query->where('team_id', $this->outlet->id))
                ->with(['stockOpname', 'product'])->orderBy('id')->get()
                ->map(fn (StockOpnameItem $row) => [$row->stockOpname->number, $row->product?->sku, (float) $row->system_quantity, (float) $row->counted_quantity])->all(),
            default => [],
        };
    }
}
