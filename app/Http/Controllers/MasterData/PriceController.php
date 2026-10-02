<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Price;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PriceController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Price::class;
    }

    protected function permissionPrefix(): string
    {
        return 'prices';
    }

    protected function pagePath(): string
    {
        return 'master-data/prices';
    }

    protected function routePrefix(): string
    {
        return 'master-data.prices';
    }

    protected function label(): string
    {
        return 'Price';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  Price  $record
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
