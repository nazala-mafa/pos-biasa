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
    $admin = User::factory()->create()->assignRole('admin');
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

    $user = User::factory()->create()->assignRole('admin');
    $this->actingAs($user)->get(route('admin.outlets.export', $this->defaultOutlet))->assertForbidden();
    $this->actingAs($user)->post(route('admin.outlets.import', $this->defaultOutlet), [])->assertForbidden();
});

test('the master data seeder imports the bundled workbook into the pos outlet', function () {
    $this->seed(MasterDataSeeder::class);

    expect($this->defaultOutlet->productCategories()->pluck('name')->all())->toEqualCanonicalizing(['Food', 'Beverage', 'Other'])
        ->and($this->defaultOutlet->taxes()->sole()->name)->toBe('PPN');
});
