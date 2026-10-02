<?php

use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
});

dataset('records', [
    'products' => ['products', Product::class, ['sku' => 'SKU-1', 'name' => 'Kopi', 'cost_price' => 1000, 'selling_price' => 2000, 'min_stock' => 2, 'is_active' => true]],
    'customers' => ['customers', Customer::class, ['name' => 'Budi', 'phone' => '0812', 'email' => 'budi@example.com', 'address' => null]],
    'suppliers' => ['suppliers', Supplier::class, ['name' => 'PT Maju', 'contact_person' => 'Sari', 'phone' => null, 'email' => null, 'address' => null]],
]);

test('admin can list only records of the current outlet', function (string $key, string $model) {
    $model::factory()->count(2)->create(['team_id' => $this->outletId]);
    $model::factory()->create();

    $this->actingAs($this->admin)
        ->get(route("{$key}.index"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component("{$key}/index")->has('rows', 2));
})->with('records');

test('admin can create, update and delete a record', function (string $key, string $model, array $payload) {
    $this->actingAs($this->admin)->post(route("{$key}.store"), $payload)->assertRedirect(route("{$key}.index"));

    $record = $model::firstOrFail();
    expect($record->team_id)->toBe($this->outletId);

    $this->actingAs($this->admin)
        ->put(route("{$key}.update", $record->id), [...$payload, 'name' => 'Renamed'])
        ->assertRedirect(route("{$key}.index"));
    expect($record->fresh()->name)->toBe('Renamed');

    $this->actingAs($this->admin)->delete(route("{$key}.destroy", $record->id))->assertRedirect(route("{$key}.index"));
    expect($model::count())->toBe(0);
})->with('records');

test('records of other outlets cannot be touched', function (string $key, string $model, array $payload) {
    $other = $model::factory()->create();

    $this->actingAs($this->admin)->get(route("{$key}.edit", $other->id))->assertNotFound();
    $this->actingAs($this->admin)->put(route("{$key}.update", $other->id), $payload)->assertNotFound();
    $this->actingAs($this->admin)->delete(route("{$key}.destroy", $other->id))->assertNotFound();
})->with('records');

test('users without permission are forbidden', function (string $key) {
    $user = User::factory()->create();
    $user->syncRoles([]);

    $this->actingAs($user)->get(route("{$key}.index"))->assertForbidden();
})->with(['products', 'customers', 'suppliers']);

test('product sku must be unique within the outlet and relations must belong to it', function () {
    Product::factory()->create(['team_id' => $this->outletId, 'sku' => 'DUP']);
    $foreignUnit = Unit::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('products.store'), [
            'sku' => 'DUP', 'name' => 'X', 'cost_price' => 1, 'selling_price' => 2, 'min_stock' => 0,
            'is_active' => true, 'unit_id' => $foreignUnit->id,
        ])
        ->assertSessionHasErrors(['sku', 'unit_id']);
});

test('cashier can view products but not create them', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route('products.index'))->assertOk();
    $this->actingAs($cashier)->post(route('products.store'), [])->assertForbidden();
});

test('inventory lists current stock per product', function () {
    $product = Product::factory()->create(['team_id' => $this->outletId]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $product->id, 'quantity' => 10]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $product->id, 'type' => StockMovementType::Out, 'quantity' => -4]);

    $this->actingAs($this->admin)
        ->get(route('inventory.index'))
        ->assertInertia(fn (Assert $page) => $page->component('inventory/index')->where('rows.0.stock', 6));
});

test('stock in, out and adjustment are recorded as signed quantities', function () {
    $product = Product::factory()->create(['team_id' => $this->outletId]);
    $post = fn (array $data) => $this->actingAs($this->admin)
        ->post(route('inventory.store'), ['product_id' => $product->id, ...$data]);

    $post(['type' => 'in', 'quantity' => 10])->assertRedirect(route('inventory.index'));
    $post(['type' => 'out', 'quantity' => 3])->assertRedirect(route('inventory.index'));
    $post(['type' => 'adjustment', 'quantity' => -2])->assertRedirect(route('inventory.index'));

    expect((float) $product->stockMovements()->sum('quantity'))->toBe(5.0)
        ->and($product->stockMovements()->where('user_id', $this->admin->id)->count())->toBe(3);
});

test('stock cannot go below zero', function () {
    $product = Product::factory()->create(['team_id' => $this->outletId]);

    $this->actingAs($this->admin)
        ->post(route('inventory.store'), ['product_id' => $product->id, 'type' => 'out', 'quantity' => 1])
        ->assertSessionHasErrors('quantity');

    expect(StockMovement::count())->toBe(0);
});

test('stock movement rejects products from other outlets and negative stock in', function () {
    $foreign = Product::factory()->create();
    $own = Product::factory()->create(['team_id' => $this->outletId]);

    $this->actingAs($this->admin)
        ->post(route('inventory.store'), ['product_id' => $foreign->id, 'type' => 'in', 'quantity' => 1])
        ->assertSessionHasErrors('product_id');
    $this->actingAs($this->admin)
        ->post(route('inventory.store'), ['product_id' => $own->id, 'type' => 'in', 'quantity' => -1])
        ->assertSessionHasErrors('quantity');
});

test('cashier cannot access inventory', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route('inventory.index'))->assertForbidden();
});
