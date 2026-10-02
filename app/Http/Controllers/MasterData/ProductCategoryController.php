<?php

namespace App\Http\Controllers\MasterData;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProductCategoryController extends MasterDataController
{
    protected function modelClass(): string
    {
        return ProductCategory::class;
    }

    protected function permissionPrefix(): string
    {
        return 'categories';
    }

    protected function pagePath(): string
    {
        return 'master-data/categories';
    }

    protected function routePrefix(): string
    {
        return 'master-data.categories';
    }

    protected function label(): string
    {
        return 'Category';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  ProductCategory  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'description' => $record->description,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
