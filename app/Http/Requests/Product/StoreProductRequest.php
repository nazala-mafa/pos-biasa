<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', $this->uniqueInOutlet('products', 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_category_id' => ['nullable', $this->existsInOutlet('product_categories')],
            'unit_id' => ['nullable', $this->existsInOutlet('units')],
            'tax_id' => ['nullable', $this->existsInOutlet('taxes')],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
