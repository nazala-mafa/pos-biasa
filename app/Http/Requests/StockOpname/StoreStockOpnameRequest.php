<?php

namespace App\Http\Requests\StockOpname;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('stock-opnames.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'counted_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:1000'],
            'items.*.product_id' => ['required', 'integer', 'distinct', $this->existsInOutlet('products')],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }
}
