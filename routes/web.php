<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OutletController as AdminOutletController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\MasterData\PriceController;
use App\Http\Controllers\MasterData\ProductCategoryController;
use App\Http\Controllers\MasterData\TaxController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\Pos\CustomerController;
use App\Http\Controllers\Pos\DashboardController;
use App\Http\Controllers\Pos\InventoryController;
use App\Http\Controllers\Pos\ProductController;
use App\Http\Controllers\Pos\RoleController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Pos\SupplierController;
use App\Http\Controllers\Pos\UserController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('outlets/template', [AdminOutletController::class, 'template'])->name('outlets.template');
        Route::get('outlets/{outlet}/export', [AdminOutletController::class, 'export'])->name('outlets.export');
        Route::post('outlets/{outlet}/import', [AdminOutletController::class, 'import'])->name('outlets.import');
        Route::resource('outlets', AdminOutletController::class)->except('show');
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    });

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('products', ProductController::class)->except('show');
        Route::resource('customers', CustomerController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);

        Route::prefix('inventory')
            ->name('inventory.')
            ->controller(InventoryController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('movements', 'movements')->name('movements');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
            });

        Route::prefix('management')->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::resource('roles', RoleController::class)->except('show');
        });

        Route::prefix('master-data')
            ->name('master-data.')
            ->group(function () {
                Route::resource('categories', ProductCategoryController::class)->except('show');
                Route::resource('units', UnitController::class)->except('show');
                Route::resource('prices', PriceController::class)->except('show');
                Route::resource('taxes', TaxController::class)->except('show');
            });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
