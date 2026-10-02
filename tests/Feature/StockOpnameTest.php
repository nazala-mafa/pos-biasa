<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
    $this->product = Product::factory()->create(['team_id' => $this->outletId]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $this->product->id, 'quantity' => 10]);
});

test('a stock opname adjusts stock to the counted quantity', function () {
    $this->actingAs($this->admin)
        ->post(route('stock-opnames.store'), [
            'counted_at' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'counted_quantity' => 7]],
        ])
        ->assertRedirect();

    $opname = StockOpname::with('items')->firstOrFail();
    $item = $opname->items->first();

    expect($opname->number)->toBe('SO-'.now()->format('Ymd').'-0001')
        ->and((float) $item->system_quantity)->toBe(10.0)
        ->and((float) $item->counted_quantity)->toBe(7.0)
        ->and((float) $item->difference)->toBe(-3.0)
        ->and((float) $this->product->stockMovements()->sum('quantity'))->toBe(7.0)
        ->and($this->product->stockMovements()->latest('id')->first()->type)->toBe(StockMovementType::Adjustment);
});

test('a matching count records the line without a stock movement', function () {
    $this->actingAs($this->admin)
        ->post(route('stock-opnames.store'), [
            'counted_at' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'counted_quantity' => 10]],
        ])
        ->assertRedirect();

    expect(StockOpname::firstOrFail()->items)->toHaveCount(1)
        ->and($this->product->stockMovements()->count())->toBe(1);
});

test('a stock opname needs counted items from this outlet only', function () {
    $foreign = Product::factory()->create(['team_id' => Team::factory()->create()->id]);

    $this->actingAs($this->admin)->post(route('stock-opnames.store'), ['counted_at' => now()->toDateString(), 'items' => []])
        ->assertSessionHasErrors('items');
    $this->actingAs($this->admin)->post(route('stock-opnames.store'), [
        'counted_at' => now()->toDateString(), 'items' => [['product_id' => $foreign->id, 'counted_quantity' => 1]],
    ])->assertSessionHasErrors('items.0.product_id');
    $this->actingAs($this->admin)->post(route('stock-opnames.store'), [
        'counted_at' => now()->toDateString(), 'items' => [['product_id' => $this->product->id, 'counted_quantity' => -1]],
    ])->assertSessionHasErrors('items.0.counted_quantity');
});

test('stock opnames are scoped to the outlet and protected by permissions', function () {
    StockOpname::factory()->create(['team_id' => $this->outletId]);
    $other = StockOpname::factory()->create();

    $this->actingAs($this->admin)->get(route('stock-opnames.index'))
        ->assertInertia(fn (Assert $page) => $page->component('stock-opnames/index')->has('rows', 1));
    $this->actingAs($this->admin)->get(route('stock-opnames.show', $other->id))->assertNotFound();

    $cashier = User::factory()->create()->assignRole(testRole('cashier'));
    $this->actingAs($cashier)->get(route('stock-opnames.create'))->assertForbidden();
});
