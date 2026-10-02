<?php

namespace App\Http\Requests\SaleReturn;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('returns.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['required', $this->existsInOutlet('sales')],
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.sale_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }
}
