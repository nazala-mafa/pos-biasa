<?php

namespace App\Http\Requests\Sale;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('sales.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', $this->existsInOutlet('customers')],
            'payment_method_id' => ['required', $this->existsInOutlet('payment_methods')],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', $this->existsInOutlet('products')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'paid' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
