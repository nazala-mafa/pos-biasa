<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOutletUsersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['present', 'array'],
            'user_ids.*' => [
                'integer', 'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_superadmin', false)),
            ],
        ];
    }
}
