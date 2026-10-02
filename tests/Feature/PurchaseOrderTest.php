<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
    $this->supplier = Supplier::factory()->create(['team_id' => $this->outletId]);
    $this->product = Product::factory()->create(['team_id' => $this->outletId]);
});

function orderedPurchase(object $test, float $quantity = 10): PurchaseOrder
{
    $order = PurchaseOrder::factory()->create(['team_id' => $test->outletId, 'supplier_id' => $test->supplier->id]);
    PurchaseOrderItem::factory()->create(['purchase_order_id' => $order->id, 'product_id' => $test->product->id, 'quantity' => $quantity, 'unit_cost' => 1000, 'subtotal' => $quantity * 1000]);

    return $order->load('items');
}

test('a purchase order is created with totals computed server side', function () {
    $this->actingAs($this->admin)
        ->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'ordered_at' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 4, 'unit_cost' => 2500]],
        ])
        ->assertRedirect();

    $order = PurchaseOrder::with('items')->firstOrFail();

    expect($order->team_id)->toBe($this->outletId)
        ->and($order->status)->toBe(PurchaseOrderStatus::Ordered)
        ->and($order->number)->toBe('PO-'.now()->format('Ymd').'-0001')
        ->and((float) $order->total)->toBe(10000.0)
        ->and($order->items)->toHaveCount(1);
});

test('purchase orders reject suppliers and products of other outlets', function () {
    $foreignProduct = Product::factory()->create(['team_id' => Team::factory()->create()->id]);
    $foreignSupplier = Supplier::factory()->create(['team_id' => $foreignProduct->team_id]);

    $this->actingAs($this->admin)
        ->post(route('purchases.store'), [
            'supplier_id' => $foreignSupplier->id,
            'ordered_at' => now()->toDateString(),
            'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1, 'unit_cost' => 1]],
        ])
        ->assertSessionHasErrors(['supplier_id', 'items.0.product_id']);
});

test('receiving goods adds stock and moves the order from partial to received', function () {
    $order = orderedPurchase($this);
    $item = $order->items->first();

    $this->actingAs($this->admin)
        ->post(route('purchases.receive', $order->id), [
            'received_at' => now()->toDateString(),
            'items' => [['purchase_order_item_id' => $item->id, 'quantity' => 4]],
        ])
        ->assertRedirect(route('purchases.show', $order->id));

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Partial)
        ->and((float) $item->fresh()->received_quantity)->toBe(4.0)
        ->and((float) $this->product->stockMovements()->sum('quantity'))->toBe(4.0);

    $this->actingAs($this->admin)
        ->post(route('purchases.receive', $order->id), [
            'received_at' => now()->toDateString(),
            'items' => [['purchase_order_item_id' => $item->id, 'quantity' => 6]],
        ]);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and((float) $this->product->stockMovements()->sum('quantity'))->toBe(10.0)
        ->and($order->receipts()->count())->toBe(2);
});

test('receiving more than ordered or on a closed order is rejected', function () {
    $order = orderedPurchase($this, 5);
    $item = $order->items->first();
    $payload = fn (float $quantity) => ['received_at' => now()->toDateString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity' => $quantity]]];

    $this->actingAs($this->admin)->post(route('purchases.receive', $order->id), $payload(6))->assertSessionHasErrors('items');
    expect($this->product->stockMovements()->count())->toBe(0)->and($order->receipts()->count())->toBe(0);

    $this->actingAs($this->admin)->post(route('purchases.cancel', $order->id))->assertRedirect();
    $this->actingAs($this->admin)->post(route('purchases.receive', $order->id), $payload(1))->assertSessionHasErrors('items');
});

test('only orders without received goods can be cancelled', function () {
    $order = orderedPurchase($this);
    $this->actingAs($this->admin)->post(route('purchases.receive', $order->id), [
        'received_at' => now()->toDateString(),
        'items' => [['purchase_order_item_id' => $order->items->first()->id, 'quantity' => 1]],
    ]);

    $this->actingAs($this->admin)->post(route('purchases.cancel', $order->id))->assertSessionHasErrors('status');
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Partial);
});

test('purchase orders are scoped to the outlet and protected by permissions', function () {
    orderedPurchase($this);
    $other = PurchaseOrder::factory()->create();

    $this->actingAs($this->admin)->get(route('purchases.index'))
        ->assertInertia(fn (Assert $page) => $page->component('purchases/index')->has('rows', 1));
    $this->actingAs($this->admin)->get(route('purchases.show', $other->id))->assertNotFound();

    $cashier = User::factory()->create()->assignRole(testRole('cashier'));
    $this->actingAs($cashier)->get(route('purchases.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('purchases.store'), [])->assertForbidden();
});
