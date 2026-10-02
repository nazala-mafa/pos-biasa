<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TaxController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Tax::class;
    }

    protected function permissionPrefix(): string
    {
        return 'taxes';
    }

    protected function pagePath(): string
    {
        return 'master-data/taxes';
    }

    protected function routePrefix(): string
    {
        return 'master-data.taxes';
    }

    protected function label(): string
    {
        return 'Tax';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'rate' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    /**
     * @param  Tax  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'rate' => (float) $record->rate,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
