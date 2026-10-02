<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
    $this->product = Product::factory()->create(['team_id' => $this->outletId]);
    $this->sale = Sale::factory()->create(['team_id' => $this->outletId]);
    $this->item = SaleItem::factory()->create([
        'sale_id' => $this->sale->id, 'product_id' => $this->product->id,
        'quantity' => 4, 'unit_price' => 10000, 'tax_rate' => 10, 'tax_amount' => 4000, 'subtotal' => 40000,
    ]);
});

test('a return refunds pro rata including tax and puts the goods back in stock', function () {
    $this->actingAs($this->admin)
        ->post(route('returns.store'), [
            'sale_id' => $this->sale->id,
            'reason' => 'Damaged',
            'items' => [['sale_item_id' => $this->item->id, 'quantity' => 1]],
        ])
        ->assertRedirect();

    $return = SaleReturn::with('items')->firstOrFail();

    expect($return->team_id)->toBe($this->outletId)
        ->and($return->number)->toBe('RET-'.now()->format('Ymd').'-0001')
        ->and((float) $return->total)->toBe(11000.0)
        ->and($return->items)->toHaveCount(1)
        ->and((float) StockMovement::where('product_id', $this->product->id)->sum('quantity'))->toBe(1.0);
});

test('returns cannot exceed the quantity sold across several returns', function () {
    $payload = fn (float $quantity) => ['sale_id' => $this->sale->id, 'items' => [['sale_item_id' => $this->item->id, 'quantity' => $quantity]]];

    $this->actingAs($this->admin)->post(route('returns.store'), $payload(3))->assertRedirect();
    $this->actingAs($this->admin)->post(route('returns.store'), $payload(2))->assertSessionHasErrors('items');

    expect(SaleReturn::count())->toBe(1)
        ->and((float) StockMovement::where('product_id', $this->product->id)->sum('quantity'))->toBe(3.0);

    $this->actingAs($this->admin)->post(route('returns.store'), $payload(1))->assertRedirect();
});

test('a return needs at least one returned quantity and a sale of the same outlet', function () {
    $this->actingAs($this->admin)
        ->post(route('returns.store'), ['sale_id' => $this->sale->id, 'items' => [['sale_item_id' => $this->item->id, 'quantity' => 0]]])
        ->assertSessionHasErrors('items');

    $foreign = Sale::factory()->create();
    $this->actingAs($this->admin)
        ->post(route('returns.store'), ['sale_id' => $foreign->id, 'items' => [['sale_item_id' => $this->item->id, 'quantity' => 1]]])
        ->assertSessionHasErrors('sale_id');
});

test('the return form loads the sale lines with returnable quantities', function () {
    $this->actingAs($this->admin)->post(route('returns.store'), ['sale_id' => $this->sale->id, 'items' => [['sale_item_id' => $this->item->id, 'quantity' => 1]]]);

    $this->actingAs($this->admin)
        ->get(route('returns.create', ['sale_id' => $this->sale->id]))
        ->assertInertia(fn ($page) => $page->component('returns/form')->where('sale.lines.0.returnable', 3));
});

test('cashiers cannot process returns', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route('returns.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('returns.store'), [])->assertForbidden();
});
