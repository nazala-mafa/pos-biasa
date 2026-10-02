<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloseShiftRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('shifts.close');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closing_cash' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
