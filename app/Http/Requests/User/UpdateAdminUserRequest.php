<?php

namespace App\Http\Requests\User;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
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
            ...$this->profileRules($this->routeUser()->id),
            'password' => ['nullable', ...array_slice($this->passwordRules(), 1)],
        ];
    }

    /**
     * Get the user bound to the route.
     */
    private function routeUser(): User
    {
        $user = $this->route('user');

        abort_if(! $user instanceof User, 404);

        return $user;
    }
}
