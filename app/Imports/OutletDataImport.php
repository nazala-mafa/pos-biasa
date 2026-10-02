<?php

namespace App\Imports;

use App\Enums\StockMovementType;
use App\Exports\OutletDataExport;
use App\Models\Team;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

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

    public function onUnknownSheet($sheetName): void
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

        $productId = $outlet->products()->where('sku', $data['product_sku'])->value('id');

        if ($productId === null) {
            return __('Unknown product ":sku".', ['sku' => $data['product_sku']]);
        }

        $supplierName = $row['supplier'] ?? null;
        $supplierId = $supplierName === null ? null : $outlet->suppliers()->where('name', $supplierName)->value('id');

        if ($supplierName !== null && $supplierId === null) {
            return __('Unknown supplier ":name".', ['name' => $supplierName]);
        }

        $outlet->stockMovements()->create([
            'product_id' => $productId,
            'supplier_id' => $supplierId,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'note' => $data['note'],
        ]);

        return null;
    }
}
