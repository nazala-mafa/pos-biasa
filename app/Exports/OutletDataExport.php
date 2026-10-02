<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
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
        'products' => ['sku', 'name', 'category', 'unit', 'tax', 'description', 'cost_price', 'selling_price', 'min_stock', 'is_active'],
        'customers' => ['name', 'phone', 'email', 'address'],
        'suppliers' => ['name', 'contact_person', 'phone', 'email', 'address'],
        'stock_movements' => ['product_sku', 'supplier', 'type', 'quantity', 'note'],
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
                ->map(fn (StockMovement $row) => [$row->product?->sku, $row->supplier?->name, $row->type->value, (float) $row->quantity, $row->note])->all(),
        };
    }
}
