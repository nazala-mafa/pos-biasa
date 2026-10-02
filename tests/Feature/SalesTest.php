<?php

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Tax;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outletId = $this->admin->current_team_id;
    $this->cashId = PaymentMethod::where('team_id', $this->outletId)->where('name', 'Cash')->value('id');
});

function stockedProduct(int $outletId, int $stock, array $attributes = []): Product
{
    $product = Product::factory()->create(['team_id' => $outletId, ...$attributes]);
    StockMovement::factory()->create(['team_id' => $outletId, 'product_id' => $product->id, 'quantity' => $stock]);

    return $product;
}

test('checkout prices lines server side, deducts stock and returns the change', function () {
    $tax = Tax::factory()->create(['team_id' => $this->outletId, 'rate' => 10]);
    $product = stockedProduct($this->outletId, 10, ['selling_price' => 10000, 'tax_id' => $tax->id]);

    $this->actingAs($this->admin)
        ->post(route('sales.store'), [
            'payment_method_id' => $this->cashId,
            'discount' => 1000,
            'paid' => 50000,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])
        ->assertRedirect();

    $sale = Sale::with('items')->firstOrFail();

    expect($sale->team_id)->toBe($this->outletId)
        ->and($sale->user_id)->toBe($this->admin->id)
        ->and((float) $sale->subtotal)->toBe(30000.0)
        ->and((float) $sale->tax_total)->toBe(3000.0)
        ->and((float) $sale->total)->toBe(32000.0)
        ->and((float) $sale->change)->toBe(18000.0)
        ->and($sale->invoice_number)->toStartWith('INV-'.now()->format('Ymd').'-0001')
        ->and($sale->items)->toHaveCount(1)
        ->and((float) $product->stockMovements()->sum('quantity'))->toBe(7.0);
});

test('checkout is rejected when stock is insufficient or payment is short', function () {
    $product = stockedProduct($this->outletId, 2, ['selling_price' => 1000]);

    $this->actingAs($this->admin)
        ->post(route('sales.store'), ['payment_method_id' => $this->cashId, 'paid' => 5000, 'items' => [['product_id' => $product->id, 'quantity' => 3]]])
        ->assertSessionHasErrors('items');

    $this->actingAs($this->admin)
        ->post(route('sales.store'), ['payment_method_id' => $this->cashId, 'paid' => 1500, 'items' => [['product_id' => $product->id, 'quantity' => 2]]])
        ->assertSessionHasErrors('paid');

    expect(Sale::count())->toBe(0)->and((float) $product->stockMovements()->sum('quantity'))->toBe(2.0);
});

test('checkout rejects products and customers of other outlets', function () {
    $foreignProduct = stockedProduct(Team::factory()->create()->id, 5);
    $foreignCustomer = Customer::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('sales.store'), [
            'payment_method_id' => $this->cashId, 'paid' => 1, 'customer_id' => $foreignCustomer->id,
            'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
        ])
        ->assertSessionHasErrors(['customer_id', 'items.0.product_id']);
});

test('sales are listed per outlet and only visible within it', function () {
    Sale::factory()->count(2)->create(['team_id' => $this->outletId]);
    $other = Sale::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('sales.index'))
        ->assertInertia(fn (Assert $page) => $page->component('sales/index')->has('rows', 2));

    $this->actingAs($this->admin)->get(route('sales.show', $other->id))->assertNotFound();
});

test('cashier can sell but users without permission cannot open sales', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));
    $this->actingAs($cashier)->get(route('sales.create'))->assertOk();

    $nobody = User::factory()->create();
    $nobody->syncRoles([]);
    $this->actingAs($nobody)->get(route('sales.index'))->assertForbidden();
    $this->actingAs($nobody)->post(route('sales.store'), [])->assertForbidden();
});

test('product photo is stored, replaced and removed with the product', function () {
    Storage::fake('public');
    $payload = ['sku' => 'P1', 'name' => 'Kopi', 'cost_price' => 1, 'selling_price' => 2, 'min_stock' => 0, 'is_active' => true];

    $this->actingAs($this->admin)
        ->post(route('products.store'), [...$payload, 'photo' => UploadedFile::fake()->image('a.jpg')])
        ->assertRedirect(route('products.index'));

    $product = Product::firstOrFail();
    $firstPath = $product->photo_path;
    Storage::disk('public')->assertExists($firstPath);

    $this->actingAs($this->admin)
        ->post(route('products.update', $product->id), ['_method' => 'put', ...$payload, 'photo' => UploadedFile::fake()->image('b.png')]);
    $secondPath = $product->fresh()->photo_path;
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    $this->actingAs($this->admin)
        ->post(route('products.update', $product->id), ['_method' => 'put', ...$payload, 'remove_photo' => true]);
    expect($product->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($secondPath);
});

test('product photo must be an image under 2 MB', function () {
    Storage::fake('public');
    $payload = ['sku' => 'P1', 'name' => 'Kopi', 'cost_price' => 1, 'selling_price' => 2, 'min_stock' => 0, 'is_active' => true];

    $this->actingAs($this->admin)->post(route('products.store'), [...$payload, 'photo' => UploadedFile::fake()->create('a.pdf', 10)])->assertSessionHasErrors('photo');
    $this->actingAs($this->admin)->post(route('products.store'), [...$payload, 'photo' => UploadedFile::fake()->image('big.jpg')->size(3000)])->assertSessionHasErrors('photo');
});
