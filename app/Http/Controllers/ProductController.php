<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MasterData\MasterDataController;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function permissionPrefix(): string
    {
        return 'products';
    }

    protected function pagePath(): string
    {
        return 'products';
    }

    protected function routePrefix(): string
    {
        return 'products';
    }

    protected function label(): string
    {
        return 'Product';
    }

    protected function relations(): array
    {
        return ['productCategory:id,name', 'unit:id,name,abbreviation', 'tax:id,name,rate'];
    }

    protected function formOptions(Request $request): array
    {
        $outlet = $this->outlet($request);

        return [
            'categories' => $outlet->productCategories()->orderBy('name')->get(['id', 'name']),
            'units' => $outlet->units()->orderBy('name')->get(['id', 'name', 'abbreviation']),
            'taxes' => $outlet->taxes()->orderBy('name')->get(['id', 'name', 'rate']),
        ];
    }

    protected function rules(Request $request, ?Model $record): array
    {
        $outletId = $this->outlet($request)->id;
        $outletScoped = fn (string $table) => Rule::exists($table, 'id')->where('team_id', $outletId);

        return [
            'sku' => [
                'required', 'string', 'max:64',
                Rule::unique('products', 'sku')->where('team_id', $outletId)->ignore($record?->getKey()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_category_id' => ['nullable', $outletScoped('product_categories')],
            'unit_id' => ['nullable', $outletScoped('units')],
            'tax_id' => ['nullable', $outletScoped('taxes')],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  Product  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'sku' => $record->sku,
            'name' => $record->name,
            'description' => $record->description,
            'categoryId' => $record->product_category_id,
            'category' => $record->productCategory?->name,
            'unitId' => $record->unit_id,
            'unit' => $record->unit?->abbreviation,
            'taxId' => $record->tax_id,
            'tax' => $record->tax?->name,
            'costPrice' => (float) $record->cost_price,
            'sellingPrice' => (float) $record->selling_price,
            'minStock' => (float) $record->min_stock,
            'isActive' => $record->is_active,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
