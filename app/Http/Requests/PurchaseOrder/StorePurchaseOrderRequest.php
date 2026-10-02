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

    /**
     * The validated input, shaped for the repository that persists it.
     *
     * @param  array<array-key, mixed>|int|string|null  $key
     * @param  mixed  $default
     * @return array{supplier_id: int, ordered_at: string, expected_at?: string|null, note?: string|null, items: array<int, array{product_id: int, quantity: float|int|string, unit_cost: float|int|string}>}
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
