<?php

use App\Models\Option;
use App\Models\Team;
use App\Models\User;

test('scalar values round trip without json', function () {
    Option::put('app.name', 'Toko');

    expect(Option::get('app.name'))->toBe('Toko')
        ->and(Option::first()->is_json)->toBeFalse();
});

test('arrays are stored as json and decoded back', function () {
    Option::put('receipt', ['width' => 58, 'footer' => 'Terima kasih']);

    expect(Option::first()->is_json)->toBeTrue()
        ->and(Option::get('receipt'))->toBe(['width' => 58, 'footer' => 'Terima kasih']);
});

test('put updates the existing option instead of duplicating', function () {
    Option::put('theme', 'light');
    Option::put('theme', 'dark');

    expect(Option::count())->toBe(1)
        ->and(Option::get('theme'))->toBe('dark');
});

test('scopes are isolated from each other', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    Option::put('theme', 'global');
    Option::put('theme', 'outlet', teamId: $team->id);
    Option::put('theme', 'personal', userId: $user->id);
    Option::put('theme', 'personal-outlet', teamId: $team->id, userId: $user->id);

    expect(Option::get('theme'))->toBe('global')
        ->and(Option::get('theme', teamId: $team->id))->toBe('outlet')
        ->and(Option::get('theme', userId: $user->id))->toBe('personal')
        ->and(Option::get('theme', teamId: $team->id, userId: $user->id))->toBe('personal-outlet');
});

test('get returns the default when missing and forget removes the option', function () {
    expect(Option::get('missing', 'fallback'))->toBe('fallback');

    Option::put('temp', 1);
    Option::forget('temp');

    expect(Option::get('temp'))->toBeNull();
});
