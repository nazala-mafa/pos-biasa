<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('purchases.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', $this->existsInOutlet('suppliers')],
            'ordered_at' => ['required', 'date'],
            'expected_at' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'distinct', $this->existsInOutlet('products')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }
}
