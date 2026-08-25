<?php

namespace App\Providers;

use App\Models\CompanySetting;
use App\Models\SparePart;
use App\Models\VehicleStock;
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

                $lowCount = cache()->remember('low_stock_count', 300, function () {
                    return SparePart::lowStock()->count()
                        + VehicleStock::whereColumn('current_stock', '<=', 'reorder_level')->count();
                });
            } catch (\Throwable $e) {
                $company  = new CompanySetting(['company_name' => 'Abush Spare Part', 'currency_symbol' => 'Br']);
                $lowCount = 0;
            }

            $view->with(compact('company', 'lowCount'));
        });
    }
}
