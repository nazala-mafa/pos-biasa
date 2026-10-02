<?php

namespace App\Http\Requests\User;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->route('user')->id),
            'password' => ['nullable', ...array_slice($this->passwordRules(), 1)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $this->user()->current_team_id))],
        ];
    }
}
