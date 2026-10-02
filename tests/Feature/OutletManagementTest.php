<?php

use App\Actions\Outlets\ImportOutletData;
use App\Actions\Teams\AssignDefaultTeam;
use App\Exports\OutletDataExport;
use App\Exports\OutletDataSheetExport;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->superadmin()->create();
    $this->defaultOutlet = Team::create(['name' => AssignDefaultTeam::NAME]);
});

test('superadmin can list outlets', function () {
    Team::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.outlets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/outlets/index')->has('outlets', 3));
});

test('non superadmin users cannot manage outlets, even with the admin role', function () {
    $admin = User::factory()->create()->assignRole(testRole('admin'));
    $outlet = Team::factory()->create();

    $this->actingAs($admin)->get(route('admin.outlets.index'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.outlets.store'), ['name' => 'X'])->assertForbidden();
    $this->actingAs($admin)->put(route('admin.outlets.update', $outlet), ['name' => 'Y'])->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.outlets.destroy', $outlet))->assertForbidden();
});

test('superadmin can create an outlet', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.outlets.store'), ['name' => 'Cabang Bandung'])
        ->assertRedirect(route('admin.outlets.index'));

    expect(Team::where('name', 'Cabang Bandung')->exists())->toBeTrue();
});

test('outlet name is required, unique and not reserved', function () {
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => AssignDefaultTeam::NAME])->assertSessionHasErrors('name');
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => 'admin'])->assertSessionHasErrors('name');
});

test('superadmin can update an outlet', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.update', $outlet), ['name' => 'Renamed'])
        ->assertRedirect(route('admin.outlets.index'));

    expect($outlet->refresh()->name)->toBe('Renamed');
});

test('superadmin can delete an empty outlet', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $outlet))->assertRedirect(route('admin.outlets.index'));

    $this->assertSoftDeleted($outlet);
});

test('outlet with users cannot be deleted', function () {
    $outlet = Team::factory()->create();
    $outlet->memberships()->create(['user_id' => $this->admin->id, 'role' => 'member']);

    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $outlet))->assertRedirect(route('admin.outlets.index'));

    $this->assertNotSoftDeleted($outlet);
});

test('default outlet cannot be modified or deleted', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.outlets.update', $this->defaultOutlet), ['name' => 'Other'])
        ->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $this->defaultOutlet))->assertForbidden();
});

test('superadmin can open the dashboard of an outlet they are not a member of', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('dashboard', $outlet))
        ->assertOk();

    expect($this->admin->refresh()->current_team_id)->toBe($outlet->id);
});

test('superadmin can export an outlet to excel', function () {
    Excel::fake();

    $this->actingAs($this->admin)->get(route('admin.outlets.export', $this->defaultOutlet))->assertOk();

    Excel::assertDownloaded($this->defaultOutlet->slug.'.xlsx');
});

test('superadmin can download the import template', function () {
    Excel::fake();

    $this->actingAs($this->admin)->get(route('admin.outlets.template'))->assertOk();

    Excel::assertDownloaded('outlet-template.xlsx');
});

test('exporting then importing an outlet copies every menu and skips invalid rows', function () {
    $source = Team::factory()->create();
    $category = $source->productCategories()->create(['name' => 'Food']);
    $unit = $source->units()->create(['name' => 'Piece', 'abbreviation' => 'pcs']);
    $tax = $source->taxes()->create(['name' => 'PPN', 'rate' => 11]);
    $source->prices()->create(['name' => 'Retail']);
    $supplier = $source->suppliers()->create(['name' => 'Acme']);
    $source->customers()->create(['name' => 'Budi', 'email' => 'budi@example.com']);
    $product = $source->products()->create([
        'sku' => 'SKU-1', 'name' => 'Nasi', 'product_category_id' => $category->id, 'unit_id' => $unit->id,
        'tax_id' => $tax->id, 'selling_price' => 15000,
    ]);
    $source->stockMovements()->create(['product_id' => $product->id, 'supplier_id' => $supplier->id, 'type' => 'in', 'quantity' => 5]);

    $path = tempnam(sys_get_temp_dir(), 'outlet').'.xlsx';
    file_put_contents($path, Excel::raw(new OutletDataExport($source), ExcelType::XLSX));

    $target = Team::factory()->create();
    $file = new UploadedFile($path, 'outlet.xlsx', null, null, true);

    $this->actingAs($this->admin)
        ->post(route('admin.outlets.import', $target), ['file' => $file])
        ->assertRedirect(route('admin.outlets.edit', $target));

    $copy = $target->products()->with(['productCategory', 'unit', 'tax'])->sole();

    expect($copy->sku)->toBe('SKU-1')
        ->and($copy->productCategory->name)->toBe('Food')
        ->and($copy->unit->name)->toBe('Piece')
        ->and((float) $copy->tax->rate)->toBe(11.0)
        ->and((float) $copy->selling_price)->toBe(15000.0)
        ->and($target->prices()->count())->toBe(1)
        ->and($target->customers()->sole()->email)->toBe('budi@example.com')
        ->and($target->stockMovements()->sole()->supplier->name)->toBe('Acme');

    $this->actingAs($this->admin)->post(route('admin.outlets.import', $target), ['file' => $file]);
    expect($target->products()->count())->toBe(1)->and($target->units()->count())->toBe(1);
});

test('rows with unknown references are skipped', function () {
    $outlet = Team::factory()->create();
    $path = tempnam(sys_get_temp_dir(), 'outlet').'.xlsx';
    file_put_contents($path, Excel::raw(new class implements Export, WithMultipleSheets
    {
        public function sheets(): array
        {
            return [
                new OutletDataSheetExport('products', OutletDataExport::SHEETS['products'], [
                    ['A', 'Good', null, null, null, null, 1, 2, 0, 1],
                    ['B', 'Bad category', 'Nope', null, null, null, 1, 2, 0, 1],
                ]),
            ];
        }
    }, ExcelType::XLSX));

    $import = app(ImportOutletData::class)->handle($outlet, $path);

    expect($outlet->products()->pluck('sku')->all())->toBe(['A'])
        ->and($import->failures)->toHaveCount(1);
});

test('outlet import requires a spreadsheet file and a superadmin', function () {
    $this->actingAs($this->admin)->post(route('admin.outlets.import', $this->defaultOutlet), [])->assertSessionHasErrors('file');

    $user = User::factory()->create()->assignRole(testRole('admin'));
    $this->actingAs($user)->get(route('admin.outlets.export', $this->defaultOutlet))->assertForbidden();
    $this->actingAs($user)->post(route('admin.outlets.import', $this->defaultOutlet), [])->assertForbidden();
});

test('the master data seeder imports the bundled workbook into the pos outlet', function () {
    $this->seed(MasterDataSeeder::class);

    $outlet = $this->defaultOutlet;

    expect($outlet->productCategories()->count())->toBe(6)
        ->and($outlet->taxes()->pluck('name')->all())->toEqualCanonicalizing(['PPN', 'Bebas Pajak'])
        ->and($outlet->paymentMethods()->count())->toBe(6)
        ->and($outlet->products()->count())->toBe(32)
        ->and($outlet->suppliers()->count())->toBe(5)
        ->and($outlet->customers()->count())->toBe(12)
        ->and($outlet->purchaseOrders()->count())->toBe(14)
        ->and($outlet->goodsReceipts()->count())->toBe(11)
        ->and($outlet->sales()->count())->toBe(299)
        ->and($outlet->saleReturns()->count())->toBe(5)
        ->and($outlet->stockOpnames()->count())->toBe(2);

    $sale = $outlet->sales()->with('items')->orderBy('invoice_number')->first();

    expect($sale->created_at->format('Y-m'))->toBe('2026-09')
        ->and((float) $sale->total)->toBe(round((float) $sale->items->sum('subtotal') + (float) $sale->items->sum('tax_amount') - (float) $sale->discount, 2))
        ->and((float) $sale->paid)->toBeGreaterThanOrEqual((float) $sale->total)
        ->and($outlet->stockMovements()->whereHas('product')->sum('quantity'))->toBeGreaterThan(0)
        ->and($outlet->products()->withSum('stockMovements as stock', 'quantity')->get()->every(fn ($product) => (float) $product->stock >= 0))->toBeTrue();
});

test('exporting then importing carries transactions and keeps the totals consistent', function () {
    $this->seed(MasterDataSeeder::class);

    $path = tempnam(sys_get_temp_dir(), 'outlet').'.xlsx';
    file_put_contents($path, Excel::raw(new OutletDataExport($this->defaultOutlet), ExcelType::XLSX));

    $target = Team::factory()->create();
    $import = app(ImportOutletData::class)->handle($target, $path);

    expect($import->failures)->toBe([])
        ->and($target->sales()->count())->toBe(299)
        ->and((float) $target->sales()->sum('total'))->toBe((float) $this->defaultOutlet->sales()->sum('total'))
        ->and((float) $target->purchaseOrders()->sum('total'))->toBe((float) $this->defaultOutlet->purchaseOrders()->sum('total'))
        ->and((float) $target->saleReturns()->sum('total'))->toBe((float) $this->defaultOutlet->saleReturns()->sum('total'))
        ->and($target->stockMovements()->count())->toBe($this->defaultOutlet->stockMovements()->count());
});

test('superadmin can sync the users of an outlet', function () {
    $outlet = Team::factory()->create();
    [$kept, $removed, $added] = User::factory()->count(3)->create();
    $outlet->memberships()->createMany([
        ['user_id' => $kept->id, 'role' => 'member'],
        ['user_id' => $removed->id, 'role' => 'member'],
    ]);
    $removed->teams()->sync([$outlet->id => ['role' => 'member']]);
    $removed->update(['current_team_id' => $outlet->id]);

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.users.sync', $outlet), ['user_ids' => [$kept->id, $added->id]])
        ->assertRedirect(route('admin.outlets.edit', $outlet));

    expect($outlet->memberships()->pluck('user_id')->sort()->values()->all())->toBe([$kept->id, $added->id])
        ->and($removed->refresh()->teams()->pluck('name')->all())->toBe([AssignDefaultTeam::NAME])
        ->and($removed->current_team_id)->toBe($this->defaultOutlet->id);
});

test('the default outlet users cannot be synced and superadmins are rejected', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.users.sync', $this->defaultOutlet), ['user_ids' => []])
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.users.sync', $outlet), ['user_ids' => [$this->admin->id]])
        ->assertSessionHasErrors('user_ids.0');
});
