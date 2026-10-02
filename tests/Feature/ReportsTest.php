<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
});

test('each report page requires its own permission', function (string $route) {
    $this->actingAs($this->admin)->get(route($route))->assertOk();

    $cashier = User::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($cashier->current_team_id);
    $cashier->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route($route))->assertForbidden();
})->with(['reports.sales', 'reports.purchases', 'reports.stock', 'reports.finance', 'reports.profit-loss']);

test('sales report lists only the current outlet sales', function () {
    $sale = Sale::factory()->create(['team_id' => $this->outletId, 'total' => 25000]);
    Sale::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('reports.sales'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/sales')
            ->has('rows', 1)
            ->where('rows.0.invoice', $sale->invoice_number)
            ->where('rows.0.total', 25000));
});

test('stock report shows stock level and value per product', function () {
    $product = Product::factory()->create(['team_id' => $this->outletId, 'cost_price' => 1000, 'min_stock' => 5]);
    StockMovement::factory()->create(['team_id' => $this->outletId, 'product_id' => $product->id, 'quantity' => 3]);

    $this->actingAs($this->admin)
        ->get(route('reports.stock'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.stock', 3)
            ->where('rows.0.stockValue', 3000)
            ->where('rows.0.isLow', true));
});

test('finance report separates cash in from sales', function () {
    Sale::factory()->create(['team_id' => $this->outletId, 'total' => 40000]);

    $this->actingAs($this->admin)
        ->get(route('reports.finance'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.type', 'sale')
            ->where('rows.0.in', 40000)
            ->where('rows.0.out', 0));
});
