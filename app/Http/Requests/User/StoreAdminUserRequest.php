<?php

namespace App\Http\Requests\User;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $outletId = Team::where('slug', $this->input('outlet'))->value('id');

        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'outlet' => ['required', 'string', Rule::exists('teams', 'slug')->whereNull('deleted_at')],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('team_id', $outletId)],
            'verified' => ['boolean'],
        ];
    }
}
