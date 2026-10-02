<?php

namespace App\Http\Requests\LandingPage;

use App\Repositories\LandingPageRepository;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand_name' => ['required', 'string', 'max:100'],
            'hero_title' => ['required', 'string', 'max:150'],
            'hero_subtitle' => ['required', 'string', 'max:300'],
            'cta_label' => ['required', 'string', 'max:50'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'features' => ['array', 'max:'.LandingPageRepository::MAX_FEATURES],
            'features.*.title' => ['required', 'string', 'max:100'],
            'features.*.description' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * The validated input as landing page content, with optional fields defaulted.
     *
     * @return array{brand_name: string, hero_title: string, hero_subtitle: string, cta_label: string, footer_text: string, features: list<array{title: string, description: string}>}
     */
    public function content(): array
    {
        $data = $this->validated();

        return [
            'brand_name' => $data['brand_name'],
            'hero_title' => $data['hero_title'],
            'hero_subtitle' => $data['hero_subtitle'],
            'cta_label' => $data['cta_label'],
            'footer_text' => $data['footer_text'] ?? '',
            'features' => array_values($data['features'] ?? []),
        ];
    }
}
