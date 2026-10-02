<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UnitController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Unit::class;
    }

    protected function permissionPrefix(): string
    {
        return 'units';
    }

    protected function pagePath(): string
    {
        return 'master-data/units';
    }

    protected function routePrefix(): string
    {
        return 'master-data.units';
    }

    protected function label(): string
    {
        return 'Unit';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'abbreviation' => ['required', 'string', 'max:10'],
        ];
    }

    /**
     * @param  Unit  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'abbreviation' => $record->abbreviation,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
