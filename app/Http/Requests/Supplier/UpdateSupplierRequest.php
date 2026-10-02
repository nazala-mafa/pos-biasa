<?php

namespace App\Http\Requests\Supplier;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('supplier');
    }
}
