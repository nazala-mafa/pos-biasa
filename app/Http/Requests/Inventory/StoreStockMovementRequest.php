<?php

namespace App\Http\Requests\Inventory;

use App\Enums\StockMovementType;
use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', $this->existsInOutlet('products')],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', 'not_in:0', 'between:-9999999999999,9999999999999'],
            'supplier_id' => ['nullable', $this->existsInOutlet('suppliers')],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
