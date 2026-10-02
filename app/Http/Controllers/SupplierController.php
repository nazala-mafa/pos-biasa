<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MasterData\MasterDataController;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SupplierController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Supplier::class;
    }

    protected function permissionPrefix(): string
    {
        return 'suppliers';
    }

    protected function pagePath(): string
    {
        return 'suppliers';
    }

    protected function routePrefix(): string
    {
        return 'suppliers';
    }

    protected function label(): string
    {
        return 'Supplier';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  Supplier  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'contactPerson' => $record->contact_person,
            'contact_person' => $record->contact_person,
            'phone' => $record->phone,
            'email' => $record->email,
            'address' => $record->address,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
