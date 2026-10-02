<?php

namespace App\Http\Requests\MasterData;

class UpdateProductCategoryRequest extends StoreProductCategoryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('categories.update');
    }

    protected function recordId(): ?int
    {
        return (int) $this->route('category');
    }
}
