<?php

namespace App\Http\Requests\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('product');
    }
}
