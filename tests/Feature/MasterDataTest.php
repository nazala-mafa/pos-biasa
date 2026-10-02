<?php

use App\Models\Price;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
});

dataset('master data', [
    'categories' => ['categories', ProductCategory::class, ['name' => 'Snacks', 'description' => 'Light food']],
    'units' => ['units', Unit::class, ['name' => 'Box', 'abbreviation' => 'bx']],
    'prices' => ['prices', Price::class, ['name' => 'Member', 'description' => null]],
    'taxes' => ['taxes', Tax::class, ['name' => 'Service', 'rate' => 5.5]],
]);

test('admin can list records', function (string $key, string $model) {
    $model::factory()->count(2)->create(['team_id' => $this->admin->current_team_id]);
    $model::factory()->create();

    $this->actingAs($this->admin)
        ->get(route("master-data.{$key}.index"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component("master-data/{$key}/index")
            ->has('rows', 2));
})->with('master data');

test('admin can create, update and delete a record', function (string $key, string $model, array $payload) {
    $this->actingAs($this->admin)
        ->post(route("master-data.{$key}.store"), $payload)
        ->assertRedirect(route("master-data.{$key}.index"));

    $record = $model::where('name', $payload['name'])->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route("master-data.{$key}.update", $record->id), [...$payload, 'name' => 'Renamed'])
        ->assertRedirect(route("master-data.{$key}.index"));

    expect($record->fresh()->name)->toBe('Renamed')
        ->and($record->team_id)->toBe($this->admin->current_team_id);

    $this->actingAs($this->admin)
        ->delete(route("master-data.{$key}.destroy", $record->id))
        ->assertRedirect(route("master-data.{$key}.index"));

    expect($model::count())->toBe(0);
})->with('master data');

test('name must be unique', function (string $key, string $model, array $payload) {
    $model::factory()->create(['name' => $payload['name'], 'team_id' => $this->admin->current_team_id]);

    $this->actingAs($this->admin)
        ->post(route("master-data.{$key}.store"), $payload)
        ->assertSessionHasErrors('name');
})->with('master data');

test('records are bound to the outlet and invisible from other outlets', function (string $key, string $model, array $payload) {
    $other = $model::factory()->create();

    $this->actingAs($this->admin)->get(route("master-data.{$key}.edit", $other->id))->assertNotFound();
    $this->actingAs($this->admin)->put(route("master-data.{$key}.update", $other->id), $payload)->assertNotFound();
    $this->actingAs($this->admin)->delete(route("master-data.{$key}.destroy", $other->id))->assertNotFound();

    expect($model::whereKey($other->id)->exists())->toBeTrue();
})->with('master data');

test('the same name can be used in different outlets', function (string $key, string $model, array $payload) {
    $model::factory()->create(['name' => $payload['name']]);

    $this->actingAs($this->admin)
        ->post(route("master-data.{$key}.store"), $payload)
        ->assertSessionHasNoErrors();

    expect($model::where('name', $payload['name'])->count())->toBe(2);
})->with('master data');

test('users without the permission are forbidden', function (string $key) {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route("master-data.{$key}.index"))->assertForbidden();
    $this->actingAs($cashier)->post(route("master-data.{$key}.store"), [])->assertForbidden();
})->with(['categories', 'units', 'prices', 'taxes']);

test('managers can view but not change master data', function () {
    $manager = User::factory()->create()->assignRole(testRole('manager'));

    $this->actingAs($manager)->get(route('master-data.taxes.index'))->assertOk();
    $this->actingAs($manager)
        ->post(route('master-data.taxes.store'), ['name' => 'X', 'rate' => 1])
        ->assertForbidden();
});

test('tax rate must be between 0 and 100', function () {
    $this->actingAs($this->admin)
        ->post(route('master-data.taxes.store'), ['name' => 'Bad', 'rate' => 150])
        ->assertSessionHasErrors('rate');
});
