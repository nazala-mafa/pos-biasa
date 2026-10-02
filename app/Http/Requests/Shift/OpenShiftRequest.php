<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\Concerns\OutletRecordRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OpenShiftRequest extends FormRequest
{
    use OutletRecordRules;

    public function authorize(): bool
    {
        return $this->user()->can('shifts.open');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opening_cash' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
