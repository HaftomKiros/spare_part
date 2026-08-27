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
                    $stockCounts = cache()->remember('stock_counts_user_' . $user->id, 120, function () use ($accessibleIds) {
                        $outParts = \Illuminate\Support\Facades\DB::table('warehouse_spare_part_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->where('current_stock', '<=', 0)
                            ->count();
                        $outVehicles = \Illuminate\Support\Facades\DB::table('warehouse_vehicle_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->where('current_stock', '<=', 0)
                            ->count();
                        $lowParts = \Illuminate\Support\Facades\DB::table('warehouse_spare_part_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->where('current_stock', '>', 0)
                            ->whereColumn('current_stock', '<=', 'reorder_level')
                            ->count();
                        $lowVehicles = \Illuminate\Support\Facades\DB::table('warehouse_vehicle_stock')
                            ->whereIn('warehouse_id', $accessibleIds)
                            ->where('current_stock', '>', 0)
                            ->whereColumn('current_stock', '<=', 'reorder_level')
                            ->count();
                        return [
                            'out'   => $outParts + $outVehicles,
                            'low'   => $lowParts + $lowVehicles,
                            'total' => $outParts + $outVehicles + $lowParts + $lowVehicles,
                        ];
                    });
                    $outCount         = $stockCounts['out'];
                    $lowCount         = $stockCounts['low'];
                    $totalStockAlerts = $stockCounts['total'];
                } else {
                    $outCount = $lowCount = $totalStockAlerts = 0;
                }
            } catch (\Throwable $e) {
                $company  = new CompanySetting(['company_name' => 'Abush Spare Part', 'currency_symbol' => 'Br']);
                $lowCount = $outCount = $totalStockAlerts = 0;
            }

            $view->with(compact('company', 'lowCount', 'outCount', 'totalStockAlerts'));
        });
    }
}
