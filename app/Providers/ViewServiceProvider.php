<?php

namespace App\Providers;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Share company settings and low stock count with ALL views
        View::composer('*', function ($view) {
            try {
                $company = cache()->remember('company_settings', 3600, function () {
                    return CompanySetting::getInstance();
                });

                // Scope low stock count to the authenticated user's accessible warehouses
                $user = auth()->user();
                if ($user) {
                    $accessibleIds = $user->accessibleWarehouseIds();
                    $lowCount = cache()->remember('low_stock_count_user_' . $user->id, 120, function () use ($accessibleIds) {
                        $parts = \Illuminate\Support\Facades\DB::table('warehouse_spare_part_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->whereColumn('current_stock', '<=', 'reorder_level')
                            ->count();
                        $vehicles = \Illuminate\Support\Facades\DB::table('warehouse_vehicle_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->whereColumn('current_stock', '<=', 'reorder_level')
                            ->count();
                        return $parts + $vehicles;
                    });
                } else {
                    $lowCount = 0;
                }
            } catch (\Throwable $e) {
                $company  = new CompanySetting(['company_name' => 'Abush Spare Part', 'currency_symbol' => 'Br']);
                $lowCount = 0;
            }

            $view->with(compact('company', 'lowCount'));
        });
    }
}
