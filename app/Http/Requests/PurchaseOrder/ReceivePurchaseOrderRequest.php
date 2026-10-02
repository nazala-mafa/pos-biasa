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
}
