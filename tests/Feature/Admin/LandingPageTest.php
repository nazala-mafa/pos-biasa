<?php

use App\Models\Option;
use App\Models\User;
use App\Repositories\LandingPageRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function landingPayload(array $overrides = []): array
{
    return [
        'brand_name' => 'Toko Maju',
        'hero_title' => 'Belanja mudah',
        'hero_subtitle' => 'Kelola toko dengan cepat',
        'cta_label' => 'Masuk',
        'footer_text' => 'Semua hak dilindungi.',
        'features' => [['title' => 'Kasir', 'description' => 'Cepat']],
        ...$overrides,
    ];
}

test('home page shows default content when nothing is saved', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('content.brand_name', config('app.name'))
            ->has('content.features', 3));
});

test('home page shows saved content', function () {
    Option::put(LandingPageRepository::OPTION_KEY, landingPayload());

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.hero_title', 'Belanja mudah')
            ->has('content.features', 1));
});

test('superadmin can open and update the landing page settings', function () {
    $this->actingAs(User::factory()->superadmin()->create());

    $this->get(route('admin.landing-page.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/landing-page')->has('content'));

    $this->put(route('admin.landing-page.update'), landingPayload())
        ->assertRedirect(route('admin.landing-page.edit'));

    expect(Option::get(LandingPageRepository::OPTION_KEY)['hero_title'])->toBe('Belanja mudah');
});

test('landing page settings are validated', function () {
    $this->actingAs(User::factory()->superadmin()->create())
        ->put(route('admin.landing-page.update'), landingPayload([
            'hero_title' => '',
            'features' => array_fill(0, 7, ['title' => 'A', 'description' => 'B']),
        ]))
        ->assertSessionHasErrors(['hero_title', 'features']);
});

test('non superadmin users cannot manage the landing page', function () {
    $this->actingAs(User::factory()->create()->assignRole(testRole('admin')));

    $this->get(route('admin.landing-page.edit'))->assertForbidden();
    $this->put(route('admin.landing-page.update'), landingPayload())->assertForbidden();
});
