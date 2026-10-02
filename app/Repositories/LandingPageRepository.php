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

        return [...$this->defaults(), ...(is_array($saved) ? $saved : [])];
    }

    /**
     * @param  Content  $content
     */
    public function save(array $content): void
    {
        Option::put(self::OPTION_KEY, $content);
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
