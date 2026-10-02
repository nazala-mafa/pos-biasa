<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MasterData\MasterDataController;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CustomerController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Customer::class;
    }

    protected function permissionPrefix(): string
    {
        return 'customers';
    }

    protected function pagePath(): string
    {
        return 'customers';
    }

    protected function routePrefix(): string
    {
        return 'customers';
    }

    protected function label(): string
    {
        return 'Customer';
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueName($request, $record)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  Customer  $record
     */
    protected function row(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'phone' => $record->phone,
            'email' => $record->email,
            'address' => $record->address,
            'createdAt' => $record->created_at?->toDateString(),
        ];
    }
}
