<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('purchases.receive');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'received_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    /**
     * The validated input, shaped for the repository that persists it.
     *
     * @param  array<array-key, mixed>|int|string|null  $key
     * @param  mixed  $default
     * @return array{received_at: string, note?: string|null, items: array<int, array{purchase_order_item_id: int, quantity: float|int|string}>}
     */
    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
