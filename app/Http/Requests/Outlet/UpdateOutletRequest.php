<?php

namespace App\Http\Requests\Outlet;

use App\Models\Team;
use App\Rules\TeamName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutletRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255', new TeamName,
                Rule::unique('teams', 'name')->whereNull('deleted_at')->ignore($this->routeOutlet()->id),
            ],
        ];
    }

    /**
     * Get the outlet bound to the route.
     */
    private function routeOutlet(): Team
    {
        $outlet = $this->route('outlet');

        abort_if(! $outlet instanceof Team, 404);

        return $outlet;
    }
}
