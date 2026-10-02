<?php

namespace App\Imports;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Exports\OutletDataExport;
use App\Models\Team;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads every known sheet of a workbook, then upserts the rows into one outlet.
 */
class OutletDataImport implements Import, SkipsUnknownSheets, WithMultipleSheets
{
    /**
     * Raw rows per sheet, filled while the workbook is read.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $rows = [];

    /**
     * Imported row count per sheet.
     *
     * @var array<string, int>
     */
    public array $imported = [];

    /**
     * Row-level failures as "sheet row N: first error message".
     *
     * @var array<int, string>
     */
    public array $failures = [];

    /**
     * @return array<string, OutletDataSheetImport>
     */
    public function sheets(): array
    {
        return collect(array_keys(OutletDataExport::SHEETS))
            ->mapWithKeys(fn (string $sheet) => [$sheet => new OutletDataSheetImport($this, $sheet)])
            ->all();
    }

    public function onUnknownSheet(mixed $sheetName): void
    {
        //
    }

    public function total(): int
    {
        return array_sum($this->imported);
    }

    /**
     * Upsert the collected rows into the outlet, parents before children.
     */
    public function apply(Team $outlet): void
    {
        foreach (array_keys(OutletDataExport::SHEETS) as $sheet) {
            $this->imported[$sheet] = 0;

            foreach ($this->rows[$sheet] ?? [] as $index => $row) {
                $error = $this->{'import'.str($sheet)->studly()}($outlet, $this->clean($row));

                if ($error !== null) {
                    $this->failures[] = $sheet.' '.__('row :line: :error', ['line' => $index + 2, 'error' => $error]);

                    continue;
                }

                $this->imported[$sheet]++;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string|null>
     */
    private function clean(array $row): array
    {
        return array_map(function (mixed $value) {
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        }, $row);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     */
    private function validate(array $data, array $rules): ?string
    {
        $validator = Validator::make($data, $rules);

        return $validator->fails() ? $validator->errors()->first() : null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importCategories(Team $outlet, array $row): ?string
    {
        $data = ['name' => $row['name'] ?? null, 'description' => $row['description'] ?? null];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'description' => ['nullable', 'string']]);

        if ($error === null) {
            $outlet->productCategories()->updateOrCreate(['name' => $data['name']], ['description' => $data['description']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importUnits(Team $outlet, array $row): ?string
    {
        $data = ['name' => $row['name'] ?? null, 'abbreviation' => $row['abbreviation'] ?? null];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'abbreviation' => ['required', 'max:10']]);

        if ($error === null) {
            $outlet->units()->updateOrCreate(['name' => $data['name']], ['abbreviation' => $data['abbreviation']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importTaxes(Team $outlet, array $row): ?string
    {
        $data = ['name' => $row['name'] ?? null, 'rate' => $row['rate'] ?? null];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'rate' => ['required', 'numeric', 'between:0,100']]);

        if ($error === null) {
            $outlet->taxes()->updateOrCreate(['name' => $data['name']], ['rate' => $data['rate']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importPrices(Team $outlet, array $row): ?string
    {
        $data = ['name' => $row['name'] ?? null, 'description' => $row['description'] ?? null];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'description' => ['nullable', 'string']]);

        if ($error === null) {
            $outlet->prices()->updateOrCreate(['name' => $data['name']], ['description' => $data['description']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importProducts(Team $outlet, array $row): ?string
    {
        $data = [
            'sku' => $row['sku'] ?? null,
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'cost_price' => $row['cost_price'] ?? 0,
            'selling_price' => $row['selling_price'] ?? 0,
            'min_stock' => $row['min_stock'] ?? 0,
            'is_active' => $row['is_active'] ?? 1,
        ];

        $error = $this->validate($data, [
            'sku' => ['required', 'max:255'],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['numeric', 'min:0'],
            'selling_price' => ['numeric', 'min:0'],
            'min_stock' => ['numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $references = [];
        $lookups = ['category' => ['productCategories', 'product_category_id'], 'unit' => ['units', 'unit_id'], 'tax' => ['taxes', 'tax_id']];

        foreach ($lookups as $column => [$relation, $foreignKey]) {
            $name = $row[$column] ?? null;
            $references[$foreignKey] = $name === null ? null : $outlet->{$relation}()->where('name', $name)->value('id');

            if ($name !== null && $references[$foreignKey] === null) {
                return __('Unknown :column ":name".', ['column' => $column, 'name' => $name]);
            }
        }

        $outlet->products()->updateOrCreate(['sku' => $data['sku']], [
            'name' => $data['name'],
            'description' => $data['description'],
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'min_stock' => $data['min_stock'],
            'is_active' => (bool) $data['is_active'],
            ...$references,
        ]);

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importCustomers(Team $outlet, array $row): ?string
    {
        $data = [
            'name' => $row['name'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
        ];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'phone' => ['nullable', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string']]);

        if ($error === null) {
            $outlet->customers()->updateOrCreate(['name' => $data['name']], $data);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importSuppliers(Team $outlet, array $row): ?string
    {
        $data = [
            'name' => $row['name'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
        ];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'contact_person' => ['nullable', 'max:255'], 'phone' => ['nullable', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string']]);

        if ($error === null) {
            $outlet->suppliers()->updateOrCreate(['name' => $data['name']], $data);
        }

        return $error;
    }

    /**
     * Stock movements are a ledger, so every row is appended.
     *
     * @param  array<string, string|null>  $row
     */
    private function importStockMovements(Team $outlet, array $row): ?string
    {
        $data = [
            'date' => $row['date'] ?? null,
            'product_sku' => $row['product_sku'] ?? null,
            'type' => $row['type'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'note' => $row['note'] ?? null,
        ];

        $error = $this->validate($data, [
            'product_sku' => ['required'],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric'],
            'note' => ['nullable', 'max:255'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $date = $this->parseDate($data['date']);

        $productId = $outlet->products()->where('sku', $data['product_sku'])->value('id');

        if ($productId === null) {
            return __('Unknown product ":sku".', ['sku' => $data['product_sku']]);
        }

        $supplierName = $row['supplier'] ?? null;
        $supplierId = $supplierName === null ? null : $outlet->suppliers()->where('name', $supplierName)->value('id');

        if ($supplierName !== null && $supplierId === null) {
            return __('Unknown supplier ":name".', ['name' => $supplierName]);
        }

        $movement = $outlet->stockMovements()->make([
            'product_id' => $productId,
            'supplier_id' => $supplierId,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'note' => $data['note'],
        ]);
        $this->stamp($movement, $date)->save();

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importPaymentMethods(Team $outlet, array $row): ?string
    {
        $data = ['name' => $row['name'] ?? null, 'is_cash' => $row['is_cash'] ?? 0, 'is_active' => $row['is_active'] ?? 1];

        $error = $this->validate($data, ['name' => ['required', 'max:255'], 'is_cash' => ['boolean'], 'is_active' => ['boolean']]);

        if ($error === null) {
            $outlet->paymentMethods()->updateOrCreate(['name' => $data['name']], ['is_cash' => (bool) $data['is_cash'], 'is_active' => (bool) $data['is_active']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importPurchaseOrders(Team $outlet, array $row): ?string
    {
        $data = [
            'number' => $row['number'] ?? null,
            'supplier' => $row['supplier'] ?? null,
            'status' => $row['status'] ?? PurchaseOrderStatus::Ordered->value,
            'ordered_at' => $this->parseDate($row['ordered_at'] ?? null)?->toDateString(),
            'expected_at' => $this->parseDate($row['expected_at'] ?? null)?->toDateString(),
            'note' => $row['note'] ?? null,
        ];

        $error = $this->validate($data, [
            'number' => ['required', 'max:255'],
            'supplier' => ['required'],
            'status' => ['required', Rule::enum(PurchaseOrderStatus::class)],
            'ordered_at' => ['required', 'date'],
            'expected_at' => ['nullable', 'date'],
            'note' => ['nullable', 'max:255'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $supplierId = $outlet->suppliers()->where('name', $data['supplier'])->value('id');

        if ($supplierId === null) {
            return __('Unknown supplier ":name".', ['name' => $data['supplier']]);
        }

        $outlet->purchaseOrders()->updateOrCreate(['number' => $data['number']], [
            'supplier_id' => $supplierId,
            'status' => $data['status'],
            'ordered_at' => $data['ordered_at'],
            'expected_at' => $data['expected_at'],
            'note' => $data['note'],
        ]);

        return null;
    }

    /**
     * Line totals are derived, and the order total follows its lines.
     *
     * @param  array<string, string|null>  $row
     */
    private function importPurchaseOrderItems(Team $outlet, array $row): ?string
    {
        $data = [
            'po_number' => $row['po_number'] ?? null,
            'product_sku' => $row['product_sku'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'received_quantity' => $row['received_quantity'] ?? 0,
            'unit_cost' => $row['unit_cost'] ?? null,
        ];

        $error = $this->validate($data, [
            'po_number' => ['required'],
            'product_sku' => ['required'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'received_quantity' => ['numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $order = $outlet->purchaseOrders()->where('number', $data['po_number'])->first();

        if ($order === null) {
            return __('Unknown purchase order ":number".', ['number' => $data['po_number']]);
        }

        $productId = $outlet->products()->where('sku', $data['product_sku'])->value('id');

        if ($productId === null) {
            return __('Unknown product ":sku".', ['sku' => $data['product_sku']]);
        }

        $order->items()->updateOrCreate(['product_id' => $productId], [
            'quantity' => $data['quantity'],
            'received_quantity' => $data['received_quantity'],
            'unit_cost' => $data['unit_cost'],
            'subtotal' => round((float) $data['quantity'] * (float) $data['unit_cost'], 2),
        ]);

        $order->update(['total' => $order->items()->sum('subtotal')]);

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importGoodsReceipts(Team $outlet, array $row): ?string
    {
        $data = [
            'number' => $row['number'] ?? null,
            'po_number' => $row['po_number'] ?? null,
            'received_at' => $this->parseDate($row['received_at'] ?? null)?->toDateString(),
            'note' => $row['note'] ?? null,
        ];

        $error = $this->validate($data, ['number' => ['required', 'max:255'], 'po_number' => ['required'], 'received_at' => ['required', 'date'], 'note' => ['nullable', 'max:255']]);

        if ($error !== null) {
            return $error;
        }

        $orderId = $outlet->purchaseOrders()->where('number', $data['po_number'])->value('id');

        if ($orderId === null) {
            return __('Unknown purchase order ":number".', ['number' => $data['po_number']]);
        }

        $outlet->goodsReceipts()->updateOrCreate(['number' => $data['number']], [
            'purchase_order_id' => $orderId,
            'received_at' => $data['received_at'],
            'note' => $data['note'],
        ]);

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importGoodsReceiptItems(Team $outlet, array $row): ?string
    {
        $data = ['receipt_number' => $row['receipt_number'] ?? null, 'product_sku' => $row['product_sku'] ?? null, 'quantity' => $row['quantity'] ?? null];

        $error = $this->validate($data, ['receipt_number' => ['required'], 'product_sku' => ['required'], 'quantity' => ['required', 'numeric', 'gt:0']]);

        if ($error !== null) {
            return $error;
        }

        $receipt = $outlet->goodsReceipts()->where('number', $data['receipt_number'])->first();

        if ($receipt === null) {
            return __('Unknown goods receipt ":number".', ['number' => $data['receipt_number']]);
        }

        $orderItem = $receipt->purchaseOrder->items()->whereHas('product', fn ($query) => $query->where('sku', $data['product_sku']))->first();

        if ($orderItem === null) {
            return __('Product ":sku" is not on the purchase order.', ['sku' => $data['product_sku']]);
        }

        $receipt->items()->updateOrCreate(['purchase_order_item_id' => $orderItem->id], [
            'product_id' => $orderItem->product_id,
            'quantity' => $data['quantity'],
        ]);

        return null;
    }

    /**
     * Totals are filled by {@see self::importSaleItems()} once the lines arrive.
     *
     * @param  array<string, string|null>  $row
     */
    private function importSales(Team $outlet, array $row): ?string
    {
        $data = [
            'invoice_number' => $row['invoice_number'] ?? null,
            'date' => $row['date'] ?? null,
            'customer' => $row['customer'] ?? null,
            'payment_method' => $row['payment_method'] ?? null,
            'discount' => $row['discount'] ?? 0,
            'paid' => $row['paid'] ?? 0,
            'note' => $row['note'] ?? null,
        ];

        $error = $this->validate($data, [
            'invoice_number' => ['required', 'max:255'],
            'payment_method' => ['required'],
            'discount' => ['numeric', 'min:0'],
            'paid' => ['numeric', 'min:0'],
            'note' => ['nullable', 'max:255'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $method = $outlet->paymentMethods()->where('name', $data['payment_method'])->first();

        if ($method === null) {
            return __('Unknown payment method ":name".', ['name' => $data['payment_method']]);
        }

        $customerId = $data['customer'] === null ? null : $outlet->customers()->where('name', $data['customer'])->value('id');

        if ($data['customer'] !== null && $customerId === null) {
            return __('Unknown customer ":name".', ['name' => $data['customer']]);
        }

        $sale = $outlet->sales()->firstOrNew(['invoice_number' => $data['invoice_number']]);
        $sale->fill([
            'customer_id' => $customerId,
            'payment_method_id' => $method->id,
            'payment_method' => $method->name,
            'discount' => $data['discount'],
            'paid' => $data['paid'],
            'note' => $data['note'],
        ]);
        $sale->subtotal ??= 0;
        $sale->tax_total ??= 0;
        $sale->total ??= 0;
        $this->stamp($sale, $this->parseDate($data['date']))->save();

        return null;
    }

    /**
     * Lines are priced from the sheet and take their name and tax rate from the product; the sale
     * totals are recalculated from all of its lines.
     *
     * @param  array<string, string|null>  $row
     */
    private function importSaleItems(Team $outlet, array $row): ?string
    {
        $data = [
            'invoice_number' => $row['invoice_number'] ?? null,
            'product_sku' => $row['product_sku'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'unit_price' => $row['unit_price'] ?? null,
        ];

        $error = $this->validate($data, ['invoice_number' => ['required'], 'product_sku' => ['required'], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_price' => ['required', 'numeric', 'min:0']]);

        if ($error !== null) {
            return $error;
        }

        $sale = $outlet->sales()->where('invoice_number', $data['invoice_number'])->first();

        if ($sale === null) {
            return __('Unknown sale ":number".', ['number' => $data['invoice_number']]);
        }

        $product = $outlet->products()->with('tax:id,rate')->where('sku', $data['product_sku'])->first();

        if ($product === null) {
            return __('Unknown product ":sku".', ['sku' => $data['product_sku']]);
        }

        $subtotal = round((float) $data['unit_price'] * (float) $data['quantity'], 2);
        $taxRate = (float) ($product->tax->rate ?? 0);

        $sale->items()->updateOrCreate(['sku' => $product->sku], [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'tax_rate' => $taxRate,
            'tax_amount' => round($subtotal * $taxRate / 100, 2),
            'subtotal' => $subtotal,
        ]);

        $subtotal = (float) $sale->items()->sum('subtotal');
        $taxTotal = (float) $sale->items()->sum('tax_amount');
        $total = round($subtotal + $taxTotal - (float) $sale->discount, 2);

        $sale->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'change' => max(round((float) $sale->paid - $total, 2), 0),
        ])->saveQuietly();

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importSaleReturns(Team $outlet, array $row): ?string
    {
        $data = [
            'number' => $row['number'] ?? null,
            'invoice_number' => $row['invoice_number'] ?? null,
            'date' => $row['date'] ?? null,
            'reason' => $row['reason'] ?? null,
        ];

        $error = $this->validate($data, ['number' => ['required', 'max:255'], 'invoice_number' => ['required'], 'reason' => ['nullable', 'max:255']]);

        if ($error !== null) {
            return $error;
        }

        $saleId = $outlet->sales()->where('invoice_number', $data['invoice_number'])->value('id');

        if ($saleId === null) {
            return __('Unknown sale ":number".', ['number' => $data['invoice_number']]);
        }

        $return = $outlet->saleReturns()->firstOrNew(['number' => $data['number']]);
        $return->fill(['sale_id' => $saleId, 'reason' => $data['reason']]);
        $return->total ??= 0;
        $this->stamp($return, $this->parseDate($data['date']))->save();

        return null;
    }

    /**
     * The refund is pro rata of the sold line (price plus tax), as when a return is made in the app.
     *
     * @param  array<string, string|null>  $row
     */
    private function importSaleReturnItems(Team $outlet, array $row): ?string
    {
        $data = ['return_number' => $row['return_number'] ?? null, 'product_sku' => $row['product_sku'] ?? null, 'quantity' => $row['quantity'] ?? null];

        $error = $this->validate($data, ['return_number' => ['required'], 'product_sku' => ['required'], 'quantity' => ['required', 'numeric', 'gt:0']]);

        if ($error !== null) {
            return $error;
        }

        $return = $outlet->saleReturns()->where('number', $data['return_number'])->first();

        if ($return === null) {
            return __('Unknown sale return ":number".', ['number' => $data['return_number']]);
        }

        $saleItem = $return->sale->items()->where('sku', $data['product_sku'])->first();

        if ($saleItem === null) {
            return __('Product ":sku" is not on the sale.', ['sku' => $data['product_sku']]);
        }

        if ((float) $data['quantity'] > (float) $saleItem->quantity) {
            return __('The quantity returned exceeds the quantity sold.');
        }

        $return->items()->updateOrCreate(['sale_item_id' => $saleItem->id], [
            'product_id' => $saleItem->product_id,
            'quantity' => $data['quantity'],
            'amount' => round(((float) $saleItem->subtotal + (float) $saleItem->tax_amount) / (float) $saleItem->quantity * (float) $data['quantity'], 2),
        ]);

        $return->update(['total' => $return->items()->sum('amount')]);

        return null;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importStockOpnames(Team $outlet, array $row): ?string
    {
        $data = ['number' => $row['number'] ?? null, 'counted_at' => $this->parseDate($row['counted_at'] ?? null)?->toDateString(), 'note' => $row['note'] ?? null];

        $error = $this->validate($data, ['number' => ['required', 'max:255'], 'counted_at' => ['required', 'date'], 'note' => ['nullable', 'max:255']]);

        if ($error === null) {
            $outlet->stockOpnames()->updateOrCreate(['number' => $data['number']], ['counted_at' => $data['counted_at'], 'note' => $data['note']]);
        }

        return $error;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importStockOpnameItems(Team $outlet, array $row): ?string
    {
        $data = [
            'opname_number' => $row['opname_number'] ?? null,
            'product_sku' => $row['product_sku'] ?? null,
            'system_quantity' => $row['system_quantity'] ?? 0,
            'counted_quantity' => $row['counted_quantity'] ?? null,
        ];

        $error = $this->validate($data, ['opname_number' => ['required'], 'product_sku' => ['required'], 'system_quantity' => ['numeric'], 'counted_quantity' => ['required', 'numeric', 'min:0']]);

        if ($error !== null) {
            return $error;
        }

        $opname = $outlet->stockOpnames()->where('number', $data['opname_number'])->first();

        if ($opname === null) {
            return __('Unknown stock opname ":number".', ['number' => $data['opname_number']]);
        }

        $productId = $outlet->products()->where('sku', $data['product_sku'])->value('id');

        if ($productId === null) {
            return __('Unknown product ":sku".', ['sku' => $data['product_sku']]);
        }

        $opname->items()->updateOrCreate(['product_id' => $productId], [
            'system_quantity' => $data['system_quantity'],
            'counted_quantity' => $data['counted_quantity'],
            'difference' => round((float) $data['counted_quantity'] - (float) $data['system_quantity'], 2),
        ]);

        return null;
    }

    /**
     * Accepts text dates as well as the serial numbers Excel stores for date cells.
     */
    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * Back-date a record when the sheet gives a date.
     */
    private function stamp(Model $model, ?Carbon $date): Model
    {
        if ($date !== null) {
            $model->setCreatedAt($date);
            $model->setUpdatedAt($date);
        }

        return $model;
    }
}
