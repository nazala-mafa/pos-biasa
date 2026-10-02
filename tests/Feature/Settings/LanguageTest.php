<?php

use App\Enums\PaymentMethod;
use App\Models\Option;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('language page is displayed', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('language.edit'))
        ->assertOk();
});

test('user can choose a language', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('language.update'), ['locale' => 'id'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('language.edit'));

    expect(Option::get('locale', userId: $user->id))->toBe('id');
});

test('unsupported languages are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('language.update'), ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');

    expect(Option::get('locale', userId: $user->id))->toBeNull();
});

test('translations use the language the user picked', function () {
    $user = User::factory()->create();
    Option::put('locale', 'id', userId: $user->id);

    $this->actingAs($user)
        ->get(route('language.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'id')
            ->where('translations.Dashboard', 'Dasbor'));
});

test('users without a choice get the default language', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('language.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->missing('translations.Dashboard'));
});

test('validation errors and enum labels are translated for the user', function () {
    $user = User::factory()->create();
    Option::put('locale', 'id', userId: $user->id);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => '', 'email' => 'bukan-email'])
        ->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
        ]);

    app()->setLocale('id');

    expect(PaymentMethod::Cash->label())->toBe('Tunai');
});
