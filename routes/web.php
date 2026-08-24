<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

// ── Catalog
use App\Http\Controllers\Catalog\VehicleTypeController;
use App\Http\Controllers\Catalog\VehicleModelController;
use App\Http\Controllers\Catalog\PartCategoryController;
use App\Http\Controllers\Catalog\SparePartController;
use App\Http\Controllers\Catalog\UnitController;

// ── Inventory
use App\Http\Controllers\Inventory\StockInController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\CurrentStockController;
use App\Http\Controllers\Inventory\StockHistoryController;

// ── Sales
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SaleReturnController;
use App\Http\Controllers\Sales\CustomerController;

// ── Purchases
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Purchases\PurchaseController;

// ── Reports
use App\Http\Controllers\Reports\ReportController;

// ── Settings
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\WarehouseController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // ── Dashboard ─────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Catalog ───────────────────────────────────
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::resource('vehicle-types',   VehicleTypeController::class);
        Route::resource('vehicle-models',  VehicleModelController::class);
        Route::resource('part-categories', PartCategoryController::class);
        Route::resource('spare-parts',     SparePartController::class);
        Route::resource('units',           UnitController::class);
    });

    // ── Inventory ─────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('stock-in',    StockInController::class);
        Route::resource('adjustments', StockAdjustmentController::class);
        Route::get('current-stock',    [CurrentStockController::class, 'index'])->name('current-stock');
        Route::get('history',          [StockHistoryController::class, 'index'])->name('history');
    });

    // ── Sales ──────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        // Sub-resources FIRST (before the main resource) to avoid {sale} swallowing them
        Route::resource('returns',   SaleReturnController::class);
        Route::resource('customers', CustomerController::class);
        Route::get('ajax/search-items', [SaleController::class, 'searchItems'])->name('ajax.search-items');

        // Main sales resource
        Route::get('/',              [SaleController::class, 'index'])->name('index');
        Route::get('/create',        [SaleController::class, 'create'])->name('create');
        Route::post('/',             [SaleController::class, 'store'])->name('store');
        Route::get('/{sale}',        [SaleController::class, 'show'])->name('show');
        Route::delete('/{sale}',     [SaleController::class, 'destroy'])->name('destroy');
        Route::get('/{sale}/invoice',[SaleController::class, 'invoice'])->name('invoice');
    });

    // ── Purchases ──────────────────────────────────
    Route::prefix('purchases')->name('purchases.')->group(function () {
        // Sub-resources FIRST
        Route::resource('suppliers', SupplierController::class);
        Route::get('ajax/search-items', [PurchaseController::class, 'searchItems'])->name('ajax.search-items');

        // Main purchases resource
        Route::get('/',                   [PurchaseController::class, 'index'])->name('index');
        Route::get('/create',             [PurchaseController::class, 'create'])->name('create');
        Route::post('/',                  [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}',         [PurchaseController::class, 'show'])->name('show');
        Route::delete('/{purchase}',      [PurchaseController::class, 'destroy'])->name('destroy');
        Route::post('/{purchase}/receive',[PurchaseController::class, 'receive'])->name('receive');
    });

    // ── Reports ────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales',       [ReportController::class, 'sales'])->name('sales');
        Route::get('vehicles',    [ReportController::class, 'vehicles'])->name('vehicles');
        Route::get('spare-parts', [ReportController::class, 'spareParts'])->name('spare-parts');
        Route::get('stock',       [ReportController::class, 'stock'])->name('stock');
        Route::get('low-stock',   [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('purchases',   [ReportController::class, 'purchases'])->name('purchases');
        Route::get('profit',      [ReportController::class, 'profit'])->name('profit');
    });

    // ── Settings ───────────────────────────────────
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('company',  [CompanyController::class, 'edit'])->name('company');
        Route::put('company',  [CompanyController::class, 'update'])->name('company.update');
        Route::resource('users',      UserController::class);
        Route::resource('roles',      RoleController::class);
        Route::resource('warehouses', WarehouseController::class);
        Route::post('warehouses/transfer', [WarehouseController::class, 'transfer'])->name('warehouses.transfer');
    });

});
