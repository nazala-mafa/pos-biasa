<?php

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
});

test('a new outlet starts with the default payment methods', function () {
    expect(PaymentMethod::where('team_id', $this->outletId)->orderBy('name')->pluck('name')->all())
        ->toBe(['Bank transfer', 'Cash', 'QRIS'])
        ->and(PaymentMethod::where('team_id', $this->outletId)->where('is_cash', true)->pluck('name')->all())->toBe(['Cash']);
});

test('payment methods are listed per outlet', function () {
    PaymentMethod::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('master-data.payment-methods.index'))
        ->assertInertia(fn (Assert $page) => $page->component('master-data/payment-methods/index')->has('rows', 3));
});

test('payment methods can be created, updated and deleted', function () {
    $this->actingAs($this->admin)
        ->post(route('master-data.payment-methods.store'), ['name' => 'Debit card', 'is_cash' => false, 'is_active' => true])
        ->assertRedirect(route('master-data.payment-methods.index'));

    $method = PaymentMethod::where('name', 'Debit card')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('master-data.payment-methods.update', $method->id), ['name' => 'Debit', 'is_cash' => false, 'is_active' => false])
        ->assertRedirect();
    expect($method->fresh())->name->toBe('Debit')->is_active->toBeFalse();

    $this->actingAs($this->admin)->delete(route('master-data.payment-methods.destroy', $method->id))->assertRedirect();
    expect(PaymentMethod::find($method->id))->toBeNull();
});

test('payment method names are unique within the outlet', function () {
    $this->actingAs($this->admin)
        ->post(route('master-data.payment-methods.store'), ['name' => 'Cash'])
        ->assertSessionHasErrors('name');
});

test('checkout rejects inactive and foreign payment methods and snapshots the name', function () {
    $product = Product::factory()->create(['team_id' => $this->outletId, 'selling_price' => 1000]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $product->id, 'quantity' => 5]);
    $inactive = PaymentMethod::factory()->create(['team_id' => $this->outletId, 'is_active' => false]);
    $foreign = PaymentMethod::factory()->create();
    $payload = ['paid' => 1000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]];

    $this->actingAs($this->admin)->post(route('sales.store'), [...$payload, 'payment_method_id' => $inactive->id])
        ->assertSessionHasErrors('payment_method_id');
    $this->actingAs($this->admin)->post(route('sales.store'), [...$payload, 'payment_method_id' => $foreign->id])
        ->assertSessionHasErrors('payment_method_id');

    $qris = PaymentMethod::where('team_id', $this->outletId)->where('name', 'QRIS')->firstOrFail();
    $this->actingAs($this->admin)->post(route('sales.store'), [...$payload, 'payment_method_id' => $qris->id])->assertRedirect();

    expect(Sale::firstOrFail())->payment_method->toBe('QRIS')->payment_method_id->toBe($qris->id);
});

test('users without permission cannot manage payment methods', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route('master-data.payment-methods.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('master-data.payment-methods.store'), ['name' => 'X'])->assertForbidden();
});
