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
use App\Http\Controllers\Inventory\StockTransferController;
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

// ── Expenses
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Expenses\ExpenseCategoryController;

// ── Settings
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\ProfileController;
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

    // ── Dashboard (always accessible to any authenticated user) ──────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profile (always accessible — every user can edit their own profile)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // ── CATALOG ──────────────────────────────────────────────────────────
    Route::prefix('catalog')->name('catalog.')->group(function () {

        // Vehicle Types
        Route::get('vehicle-types',              [VehicleTypeController::class, 'index'])  ->name('vehicle-types.index')  ->middleware('perm:catalog.vehicle-types.view');
        Route::get('vehicle-types/create',       [VehicleTypeController::class, 'create']) ->name('vehicle-types.create') ->middleware('perm:catalog.vehicle-types.create');
        Route::post('vehicle-types',             [VehicleTypeController::class, 'store'])  ->name('vehicle-types.store')  ->middleware('perm:catalog.vehicle-types.create');
        Route::get('vehicle-types/{vehicleType}',       [VehicleTypeController::class, 'show'])   ->name('vehicle-types.show')   ->middleware('perm:catalog.vehicle-types.view');
        Route::get('vehicle-types/{vehicleType}/edit',  [VehicleTypeController::class, 'edit'])   ->name('vehicle-types.edit')   ->middleware('perm:catalog.vehicle-types.edit');
        Route::put('vehicle-types/{vehicleType}',       [VehicleTypeController::class, 'update']) ->name('vehicle-types.update') ->middleware('perm:catalog.vehicle-types.edit');
        Route::patch('vehicle-types/{vehicleType}',     [VehicleTypeController::class, 'update']) ->middleware('perm:catalog.vehicle-types.edit');
        Route::delete('vehicle-types/{vehicleType}',    [VehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy')->middleware('perm:catalog.vehicle-types.delete');

        // Vehicle Models
        Route::get('vehicle-models',             [VehicleModelController::class, 'index'])  ->name('vehicle-models.index')  ->middleware('perm:catalog.vehicle-models.view');
        Route::get('vehicle-models/create',      [VehicleModelController::class, 'create']) ->name('vehicle-models.create') ->middleware('perm:catalog.vehicle-models.create');
        Route::post('vehicle-models',            [VehicleModelController::class, 'store'])  ->name('vehicle-models.store')  ->middleware('perm:catalog.vehicle-models.create');
        Route::get('vehicle-models/{vehicleModel}',      [VehicleModelController::class, 'show'])   ->name('vehicle-models.show')   ->middleware('perm:catalog.vehicle-models.view');
        Route::get('vehicle-models/{vehicleModel}/edit', [VehicleModelController::class, 'edit'])   ->name('vehicle-models.edit')   ->middleware('perm:catalog.vehicle-models.edit');
        Route::put('vehicle-models/{vehicleModel}',      [VehicleModelController::class, 'update']) ->name('vehicle-models.update') ->middleware('perm:catalog.vehicle-models.edit');
        Route::patch('vehicle-models/{vehicleModel}',    [VehicleModelController::class, 'update']) ->middleware('perm:catalog.vehicle-models.edit');
        Route::delete('vehicle-models/{vehicleModel}',   [VehicleModelController::class, 'destroy'])->name('vehicle-models.destroy')->middleware('perm:catalog.vehicle-models.delete');

        // Part Categories
        Route::get('part-categories',            [PartCategoryController::class, 'index'])  ->name('part-categories.index')  ->middleware('perm:catalog.part-categories.view');
        Route::get('part-categories/create',     [PartCategoryController::class, 'create']) ->name('part-categories.create') ->middleware('perm:catalog.part-categories.create');
        Route::post('part-categories',           [PartCategoryController::class, 'store'])  ->name('part-categories.store')  ->middleware('perm:catalog.part-categories.create');
        Route::get('part-categories/{partCategory}/edit',  [PartCategoryController::class, 'edit'])   ->name('part-categories.edit')   ->middleware('perm:catalog.part-categories.edit');
        Route::put('part-categories/{partCategory}',       [PartCategoryController::class, 'update']) ->name('part-categories.update') ->middleware('perm:catalog.part-categories.edit');
        Route::patch('part-categories/{partCategory}',     [PartCategoryController::class, 'update']) ->middleware('perm:catalog.part-categories.edit');
        Route::delete('part-categories/{partCategory}',    [PartCategoryController::class, 'destroy'])->name('part-categories.destroy')->middleware('perm:catalog.part-categories.delete');

        // Spare Parts
        Route::get('spare-parts',                [SparePartController::class, 'index'])  ->name('spare-parts.index')  ->middleware('perm:catalog.spare-parts.view');
        Route::get('spare-parts/create',         [SparePartController::class, 'create']) ->name('spare-parts.create') ->middleware('perm:catalog.spare-parts.create');
        Route::post('spare-parts',               [SparePartController::class, 'store'])  ->name('spare-parts.store')  ->middleware('perm:catalog.spare-parts.create');
        Route::get('spare-parts/{sparePart}',         [SparePartController::class, 'show'])   ->name('spare-parts.show')   ->middleware('perm:catalog.spare-parts.view');
        Route::get('spare-parts/{sparePart}/edit',    [SparePartController::class, 'edit'])   ->name('spare-parts.edit')   ->middleware('perm:catalog.spare-parts.edit');
        Route::put('spare-parts/{sparePart}',         [SparePartController::class, 'update']) ->name('spare-parts.update') ->middleware('perm:catalog.spare-parts.edit');
        Route::patch('spare-parts/{sparePart}',       [SparePartController::class, 'update']) ->middleware('perm:catalog.spare-parts.edit');
        Route::delete('spare-parts/{sparePart}',      [SparePartController::class, 'destroy'])->name('spare-parts.destroy')->middleware('perm:catalog.spare-parts.delete');

        // Units
        Route::get('units',                      [UnitController::class, 'index'])  ->name('units.index')  ->middleware('perm:catalog.units.view');
        Route::get('units/create',               [UnitController::class, 'create']) ->name('units.create') ->middleware('perm:catalog.units.create');
        Route::post('units',                     [UnitController::class, 'store'])  ->name('units.store')  ->middleware('perm:catalog.units.create');
        Route::get('units/{unit}/edit',          [UnitController::class, 'edit'])   ->name('units.edit')   ->middleware('perm:catalog.units.edit');
        Route::put('units/{unit}',               [UnitController::class, 'update']) ->name('units.update') ->middleware('perm:catalog.units.edit');
        Route::patch('units/{unit}',             [UnitController::class, 'update']) ->middleware('perm:catalog.units.edit');
        Route::delete('units/{unit}',            [UnitController::class, 'destroy'])->name('units.destroy')->middleware('perm:catalog.units.delete');
    });

    // ── INVENTORY ────────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {

        // Current Stock
        Route::get('current-stock', [CurrentStockController::class, 'index'])->name('current-stock')->middleware('perm:inventory.current-stock.view');

        // Stock Entry (Stock In)
        Route::get('stock-in',                   [StockInController::class, 'index'])         ->name('stock-in.index')          ->middleware('perm:inventory.stock-in.view');
        Route::get('stock-in/create',            [StockInController::class, 'create'])        ->name('stock-in.create')         ->middleware('perm:inventory.stock-in.create');
        Route::post('stock-in',                  [StockInController::class, 'store'])         ->name('stock-in.store')          ->middleware('perm:inventory.stock-in.create');
        Route::get('stock-in-warehouse-stock',   [StockInController::class, 'warehouseStock'])->name('stock-in.warehouse-stock')->middleware('perm:inventory.stock-in.view,inventory.stock-in.create');

        // Adjustments
        Route::get('adjustments',                [StockAdjustmentController::class, 'index']) ->name('adjustments.index') ->middleware('perm:inventory.adjustments.view');
        Route::get('adjustments/create',         [StockAdjustmentController::class, 'create'])->name('adjustments.create')->middleware('perm:inventory.adjustments.create');
        Route::post('adjustments',               [StockAdjustmentController::class, 'store']) ->name('adjustments.store') ->middleware('perm:inventory.adjustments.create');
        Route::get('adjustments/{adjustment}',   [StockAdjustmentController::class, 'show'])  ->name('adjustments.show')  ->middleware('perm:inventory.adjustments.view');

        // History
        Route::get('history', [StockHistoryController::class, 'index'])->name('history')->middleware('perm:inventory.history.view');

        // Stock Transfer
        Route::get('transfers',                  [StockTransferController::class, 'index'])         ->name('transfers.index')          ->middleware('perm:inventory.transfers.view');
        Route::get('transfers/create',           [StockTransferController::class, 'create'])        ->name('transfers.create')         ->middleware('perm:inventory.transfers.create');
        Route::post('transfers',                 [StockTransferController::class, 'store'])         ->name('transfers.store')          ->middleware('perm:inventory.transfers.create');
        Route::get('transfers/warehouse-stock',  [StockTransferController::class, 'warehouseStock']) ->name('transfers.warehouse-stock') ->middleware('perm:inventory.transfers.view,inventory.transfers.create');
        Route::get('transfers/warehouse-items',  [StockTransferController::class, 'warehouseItems']) ->name('transfers.warehouse-items') ->middleware('perm:inventory.transfers.view,inventory.transfers.create');
    });

    // ── SALES ────────────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {

        // Returns (before main resource to avoid route conflicts)
        Route::get('returns',                [SaleReturnController::class, 'index']) ->name('returns.index') ->middleware('perm:sales.returns.view');
        Route::get('returns/create',         [SaleReturnController::class, 'create'])->name('returns.create')->middleware('perm:sales.returns.create');
        Route::post('returns',               [SaleReturnController::class, 'store']) ->name('returns.store') ->middleware('perm:sales.returns.create');
        Route::get('returns/{saleReturn}',   [SaleReturnController::class, 'show'])  ->name('returns.show')  ->middleware('perm:sales.returns.view');

        // Customers
        Route::get('customers',                  [CustomerController::class, 'index'])  ->name('customers.index')  ->middleware('perm:sales.customers.view');
        Route::get('customers/create',           [CustomerController::class, 'create']) ->name('customers.create') ->middleware('perm:sales.customers.create');
        Route::post('customers',                 [CustomerController::class, 'store'])  ->name('customers.store')  ->middleware('perm:sales.customers.create');
        Route::get('customers/{customer}',       [CustomerController::class, 'show'])   ->name('customers.show')   ->middleware('perm:sales.customers.view');
        Route::get('customers/{customer}/edit',  [CustomerController::class, 'edit'])   ->name('customers.edit')   ->middleware('perm:sales.customers.edit');
        Route::put('customers/{customer}',       [CustomerController::class, 'update']) ->name('customers.update') ->middleware('perm:sales.customers.edit');
        Route::patch('customers/{customer}',     [CustomerController::class, 'update']) ->middleware('perm:sales.customers.edit');
        Route::delete('customers/{customer}',    [CustomerController::class, 'destroy'])->name('customers.destroy')->middleware('perm:sales.customers.delete');

        // AJAX — needs at least view or create permission
        Route::get('ajax/search-items',      [SaleController::class, 'searchItems'])    ->name('ajax.search-items')    ->middleware('perm:sales.view,sales.create');
        Route::get('ajax/warehouse-items',   [SaleController::class, 'warehouseItems']) ->name('ajax.warehouse-items') ->middleware('perm:sales.view,sales.create');
        Route::get('ajax/purchase-batches',  [SaleController::class, 'purchaseBatches'])->name('ajax.purchase-batches')->middleware('perm:sales.view,sales.create');

        // Main Sales
        Route::get('/',              [SaleController::class, 'index'])  ->name('index')  ->middleware('perm:sales.view');
        Route::get('/create',        [SaleController::class, 'create']) ->name('create') ->middleware('perm:sales.create');
        Route::post('/',             [SaleController::class, 'store'])  ->name('store')  ->middleware('perm:sales.create');
        Route::get('/{sale}',        [SaleController::class, 'show'])   ->name('show')   ->middleware('perm:sales.view');
        Route::get('/{sale}/edit',   [SaleController::class, 'edit'])   ->name('edit')   ->middleware('perm:sales.edit');
        Route::put('/{sale}',        [SaleController::class, 'update']) ->name('update') ->middleware('perm:sales.edit');
        Route::patch('/{sale}',      [SaleController::class, 'update'])                  ->middleware('perm:sales.edit');
        Route::delete('/{sale}',     [SaleController::class, 'destroy'])->name('destroy')->middleware('perm:sales.delete');
        Route::get('/{sale}/invoice',[SaleController::class, 'invoice'])->name('invoice')->middleware('perm:sales.view');
    });

    // ── PURCHASES ────────────────────────────────────────────────────────
    Route::prefix('purchases')->name('purchases.')->group(function () {

        // Suppliers (before main resource)
        Route::get('suppliers',                  [SupplierController::class, 'index'])  ->name('suppliers.index')  ->middleware('perm:purchases.suppliers.view');
        Route::get('suppliers/create',           [SupplierController::class, 'create']) ->name('suppliers.create') ->middleware('perm:purchases.suppliers.create');
        Route::post('suppliers',                 [SupplierController::class, 'store'])  ->name('suppliers.store')  ->middleware('perm:purchases.suppliers.create');
        Route::get('suppliers/{supplier}',       [SupplierController::class, 'show'])   ->name('suppliers.show')   ->middleware('perm:purchases.suppliers.view');
        Route::get('suppliers/{supplier}/edit',  [SupplierController::class, 'edit'])   ->name('suppliers.edit')   ->middleware('perm:purchases.suppliers.edit');
        Route::put('suppliers/{supplier}',       [SupplierController::class, 'update']) ->name('suppliers.update') ->middleware('perm:purchases.suppliers.edit');
        Route::patch('suppliers/{supplier}',     [SupplierController::class, 'update']) ->middleware('perm:purchases.suppliers.edit');
        Route::delete('suppliers/{supplier}',    [SupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('perm:purchases.suppliers.delete');

        // AJAX
        Route::get('ajax/search-items', [PurchaseController::class, 'searchItems'])->name('ajax.search-items')->middleware('perm:purchases.view,purchases.create');

        // Main Purchases
        Route::get('/',                   [PurchaseController::class, 'index'])  ->name('index')  ->middleware('perm:purchases.view');
        Route::get('/create',             [PurchaseController::class, 'create']) ->name('create') ->middleware('perm:purchases.create');
        Route::post('/',                  [PurchaseController::class, 'store'])  ->name('store')  ->middleware('perm:purchases.create');
        Route::get('/{purchase}',         [PurchaseController::class, 'show'])   ->name('show')   ->middleware('perm:purchases.view');
        Route::get('/{purchase}/edit',    [PurchaseController::class, 'edit'])   ->name('edit')   ->middleware('perm:purchases.edit');
        Route::put('/{purchase}',         [PurchaseController::class, 'update']) ->name('update') ->middleware('perm:purchases.edit');
        Route::patch('/{purchase}',       [PurchaseController::class, 'update'])                  ->middleware('perm:purchases.edit');
        Route::delete('/{purchase}',      [PurchaseController::class, 'destroy'])->name('destroy')->middleware('perm:purchases.delete');
        Route::post('/{purchase}/receive',[PurchaseController::class, 'receive'])->name('receive')->middleware('perm:purchases.create');
    });

    // ── EXPENSES ─────────────────────────────────────────────────────────
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/',                          [ExpenseController::class, 'index'])  ->name('index')  ->middleware('perm:expenses.view');
        Route::get('/create',                    [ExpenseController::class, 'create']) ->name('create') ->middleware('perm:expenses.create');
        Route::post('/',                         [ExpenseController::class, 'store'])  ->name('store')  ->middleware('perm:expenses.create');
        Route::get('/{expense}',                 [ExpenseController::class, 'show'])   ->name('show')   ->middleware('perm:expenses.view');
        Route::get('/{expense}/edit',            [ExpenseController::class, 'edit'])   ->name('edit')   ->middleware('perm:expenses.edit');
        Route::put('/{expense}',                 [ExpenseController::class, 'update']) ->name('update') ->middleware('perm:expenses.edit');
        Route::delete('/{expense}',              [ExpenseController::class, 'destroy'])->name('destroy')->middleware('perm:expenses.delete');
    });

    Route::prefix('expense-categories')->name('expense-categories.')->group(function () {
        Route::get('/',                                   [ExpenseCategoryController::class, 'index'])  ->name('index')  ->middleware('perm:expenses.view');
        Route::post('/',                                  [ExpenseCategoryController::class, 'store'])  ->name('store')  ->middleware('perm:expenses.create');
        Route::put('/{expenseCategory}',                  [ExpenseCategoryController::class, 'update']) ->name('update') ->middleware('perm:expenses.edit');
        Route::delete('/{expenseCategory}',               [ExpenseCategoryController::class, 'destroy'])->name('destroy')->middleware('perm:expenses.delete');
    });

    // ── REPORTS ──────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales',       [ReportController::class, 'sales'])     ->name('sales')      ->middleware('perm:reports.sales');
        Route::get('vehicles',    [ReportController::class, 'vehicles'])  ->name('vehicles')   ->middleware('perm:reports.vehicles');
        Route::get('spare-parts', [ReportController::class, 'spareParts'])->name('spare-parts')->middleware('perm:reports.spare-parts');
        Route::get('stock',       [ReportController::class, 'stock'])     ->name('stock')      ->middleware('perm:reports.stock');
        Route::get('low-stock',   [ReportController::class, 'lowStock'])  ->name('low-stock')  ->middleware('perm:reports.low-stock');
        Route::get('purchases',   [ReportController::class, 'purchases']) ->name('purchases')  ->middleware('perm:reports.purchases');
        Route::get('profit',      [ReportController::class, 'profit'])    ->name('profit')     ->middleware('perm:reports.profit');
        Route::get('expenses',    [ReportController::class, 'expenses'])  ->name('expenses')   ->middleware('perm:reports.expenses');
    });

    // ── SETTINGS ─────────────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->group(function () {

        // Company Profile
        Route::get('company',  [CompanyController::class, 'edit'])  ->name('company')        ->middleware('perm:settings.company');
        Route::put('company',  [CompanyController::class, 'update'])->name('company.update') ->middleware('perm:settings.company');

        // Users
        Route::get('users',                 [UserController::class, 'index'])  ->name('users.index')  ->middleware('perm:settings.users.view');
        Route::get('users/create',          [UserController::class, 'create']) ->name('users.create') ->middleware('perm:settings.users.create');
        Route::post('users',                [UserController::class, 'store'])  ->name('users.store')  ->middleware('perm:settings.users.create');
        Route::get('users/{user}',          [UserController::class, 'show'])   ->name('users.show')   ->middleware('perm:settings.users.view');
        Route::get('users/{user}/edit',     [UserController::class, 'edit'])   ->name('users.edit')   ->middleware('perm:settings.users.edit');
        Route::put('users/{user}',          [UserController::class, 'update']) ->name('users.update') ->middleware('perm:settings.users.edit');
        Route::patch('users/{user}',        [UserController::class, 'update']) ->middleware('perm:settings.users.edit');
        Route::delete('users/{user}',       [UserController::class, 'destroy'])->name('users.destroy')->middleware('perm:settings.users.delete');

        // Roles
        Route::get('roles',                 [RoleController::class, 'index'])  ->name('roles.index')  ->middleware('perm:settings.roles.view');
        Route::get('roles/create',          [RoleController::class, 'create']) ->name('roles.create') ->middleware('perm:settings.roles.create');
        Route::post('roles',                [RoleController::class, 'store'])  ->name('roles.store')  ->middleware('perm:settings.roles.create');
        Route::get('roles/{role}/edit',     [RoleController::class, 'edit'])   ->name('roles.edit')   ->middleware('perm:settings.roles.edit');
        Route::put('roles/{role}',          [RoleController::class, 'update']) ->name('roles.update') ->middleware('perm:settings.roles.edit');
        Route::patch('roles/{role}',        [RoleController::class, 'update']) ->middleware('perm:settings.roles.edit');
        Route::delete('roles/{role}',       [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('perm:settings.roles.delete');

        // Warehouses
        Route::get('warehouses',                  [WarehouseController::class, 'index'])  ->name('warehouses.index')  ->middleware('perm:settings.warehouses.view');
        Route::get('warehouses/create',           [WarehouseController::class, 'create']) ->name('warehouses.create') ->middleware('perm:settings.warehouses.create');
        Route::post('warehouses',                 [WarehouseController::class, 'store'])  ->name('warehouses.store')  ->middleware('perm:settings.warehouses.create');
        Route::get('warehouses/{warehouse}',      [WarehouseController::class, 'show'])   ->name('warehouses.show')   ->middleware('perm:settings.warehouses.view');
        Route::get('warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])   ->name('warehouses.edit')   ->middleware('perm:settings.warehouses.edit');
        Route::put('warehouses/{warehouse}',      [WarehouseController::class, 'update']) ->name('warehouses.update') ->middleware('perm:settings.warehouses.edit');
        Route::patch('warehouses/{warehouse}',    [WarehouseController::class, 'update']) ->middleware('perm:settings.warehouses.edit');
        Route::delete('warehouses/{warehouse}',   [WarehouseController::class, 'destroy'])->name('warehouses.destroy')->middleware('perm:settings.warehouses.delete');
        Route::post('warehouses/transfer',        [WarehouseController::class, 'transfer'])->name('warehouses.transfer')->middleware('perm:settings.warehouses.edit');
    });

});
