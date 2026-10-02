<?php

namespace App\Repositories;

use App\Models\Option;

/**
 * @phpstan-type Feature array{title: string, description: string}
 * @phpstan-type Content array{brand_name: string, hero_title: string, hero_subtitle: string, cta_label: string, footer_text: string, features: list<Feature>}
 */
class LandingPageRepository
{
    public const OPTION_KEY = 'landing_page';

    public const MAX_FEATURES = 6;

    /**
     * The saved landing page content merged over the defaults.
     *
     * @return Content
     */
    public function content(): array
    {
        $saved = Option::get(self::OPTION_KEY, []);
        $saved = is_array($saved) ? $saved : [];
        $defaults = $this->defaults();

        return [
            'brand_name' => $this->text($saved['brand_name'] ?? null, $defaults['brand_name']),
            'hero_title' => $this->text($saved['hero_title'] ?? null, $defaults['hero_title']),
            'hero_subtitle' => $this->text($saved['hero_subtitle'] ?? null, $defaults['hero_subtitle']),
            'cta_label' => $this->text($saved['cta_label'] ?? null, $defaults['cta_label']),
            'footer_text' => $this->text($saved['footer_text'] ?? null, $defaults['footer_text']),
            'features' => $this->features($saved['features'] ?? null, $defaults['features']),
        ];
    }

    /**
     * @param  Content  $content
     */
    public function save(array $content): void
    {
        Option::put(self::OPTION_KEY, $content);
    }

    /**
     * Fall back to the default when a saved value is missing or not a string.
     */
    private function text(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    /**
     * Keep only well formed saved features, falling back to the defaults when none was saved.
     *
     * @param  list<Feature>  $default
     * @return list<Feature>
     */
    private function features(mixed $value, array $default): array
    {
        if (! is_array($value)) {
            return $default;
        }

        $features = [];

        foreach ($value as $feature) {
            if (is_array($feature) && is_string($feature['title'] ?? null) && is_string($feature['description'] ?? null)) {
                $features[] = ['title' => $feature['title'], 'description' => $feature['description']];
            }
        }

        return $features;
    }

    /**
     * @return Content
     */
    public function defaults(): array
    {
        return [
            'brand_name' => config('app.name'),
            'hero_title' => __('Manage your store smarter'),
            'hero_subtitle' => __('Sales, stock, purchasing and reports for every outlet in one simple application.'),
            'cta_label' => __('Get started'),
            'footer_text' => __('All rights reserved.'),
            'features' => [
                ['title' => __('Fast cashier'), 'description' => __('Record sales in seconds with a cashier screen built for speed.')],
                ['title' => __('Accurate stock'), 'description' => __('Track stock movements, purchases and stock opname automatically.')],
                ['title' => __('Multi outlet'), 'description' => __('Manage many outlets with separate data and roles.')],
            ],
        ];
    }
}
