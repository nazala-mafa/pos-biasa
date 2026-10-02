<?php

use App\Models\CashierShift;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->cashier = User::factory()->create()->assignRole(testRole('cashier'));
    $this->outletId = $this->cashier->current_team_id;
});

test('a cashier opens a shift but cannot open a second one', function () {
    $this->actingAs($this->cashier)
        ->post(route('shifts.store'), ['opening_cash' => 100000])
        ->assertRedirect();

    $shift = CashierShift::firstOrFail();
    expect($shift->team_id)->toBe($this->outletId)
        ->and($shift->user_id)->toBe($this->cashier->id)
        ->and($shift->closed_at)->toBeNull();

    $this->actingAs($this->cashier)
        ->post(route('shifts.store'), ['opening_cash' => 5])
        ->assertSessionHasErrors('opening_cash');
    expect(CashierShift::count())->toBe(1);
});

test('closing a shift stores the expected cash from cash sales only', function () {
    $shift = CashierShift::factory()->create(['team_id' => $this->outletId, 'user_id' => $this->cashier->id, 'opening_cash' => 100000]);
    $cash = PaymentMethod::where('team_id', $this->outletId)->where('name', 'Cash')->firstOrFail();
    $qris = PaymentMethod::where('team_id', $this->outletId)->where('name', 'QRIS')->firstOrFail();

    Sale::factory()->create(['team_id' => $this->outletId, 'cashier_shift_id' => $shift->id, 'payment_method_id' => $cash->id, 'total' => 50000]);
    Sale::factory()->create(['team_id' => $this->outletId, 'cashier_shift_id' => $shift->id, 'payment_method_id' => $qris->id, 'total' => 70000]);

    $this->actingAs($this->cashier)
        ->post(route('shifts.close', $shift->id), ['closing_cash' => 145000])
        ->assertRedirect();

    $shift->refresh();
    expect($shift->closed_at)->not->toBeNull()
        ->and((float) $shift->expected_cash)->toBe(150000.0)
        ->and((float) $shift->closing_cash)->toBe(145000.0);

    $this->actingAs($this->cashier)
        ->post(route('shifts.close', $shift->id), ['closing_cash' => 1])
        ->assertSessionHasErrors('closing_cash');
});

test('sales made during an open shift are linked to it', function () {
    $this->actingAs($this->cashier)->post(route('shifts.store'), ['opening_cash' => 0]);
    $shift = CashierShift::firstOrFail();

    $product = Product::factory()->create(['team_id' => $this->outletId, 'selling_price' => 1000]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $product->id, 'quantity' => 5]);
    $cash = PaymentMethod::where('team_id', $this->outletId)->where('name', 'Cash')->firstOrFail();

    $this->actingAs($this->cashier)->post(route('sales.store'), [
        'payment_method_id' => $cash->id, 'paid' => 1000, 'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect();

    expect(Sale::firstOrFail()->cashier_shift_id)->toBe($shift->id);
});

test('only the shift owner can close it and other outlets cannot see it', function () {
    $shift = CashierShift::factory()->create(['team_id' => $this->outletId]);
    $foreign = CashierShift::factory()->create();

    $this->actingAs($this->cashier)->post(route('shifts.close', $shift->id), ['closing_cash' => 0])->assertForbidden();
    $this->actingAs($this->cashier)->get(route('shifts.show', $foreign->id))->assertNotFound();
});

test('users without permission cannot open shifts', function () {
    $nobody = User::factory()->create();
    $nobody->syncRoles([]);

    $this->actingAs($nobody)->get(route('shifts.index'))->assertForbidden();
    $this->actingAs($nobody)->post(route('shifts.store'), ['opening_cash' => 0])->assertForbidden();
});
