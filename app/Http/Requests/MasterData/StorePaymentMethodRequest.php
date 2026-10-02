<?php

namespace App\Http\Requests\MasterData;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('payment-methods.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueInOutlet('payment_methods')],
            'is_cash' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
