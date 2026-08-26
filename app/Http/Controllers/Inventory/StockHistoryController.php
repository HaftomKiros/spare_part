<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = StockMovement::with('user', 'vehicleModel.vehicleType', 'sparePart.category', 'warehouse')
            ->whereIn('warehouse_id', $accessibleIds); // always scope

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('vehicleModel', fn($m) =>
                    $m->where('model_name', 'like', "%{$request->search}%")
                )
                ->orWhereHas('sparePart', fn($s) =>
                    $s->where('name', 'like', "%{$request->search}%")
                      ->orWhere('part_number', 'like', "%{$request->search}%")
                );
            });
        }
        if ($request->item_type) {
            $query->where('item_type', $request->item_type);
        }
        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->warehouse_id && in_array((int)$request->warehouse_id, $accessibleIds)) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->latest()->paginate(25)->withQueryString();

        $movementTypes = [
            'purchase'       => 'Purchase',
            'sale'           => 'Sale',
            'return_in'      => 'Return In',
            'return_out'     => 'Return Out',
            'adjustment_in'  => 'Adjustment (+)',
            'adjustment_out' => 'Adjustment (-)',
            'opening'        => 'Opening Stock',
        ];

        // Summary totals — respect warehouse filter
        $baseIn  = StockMovement::whereIn('movement_type', ['purchase','return_in','adjustment_in','opening'])
            ->whereIn('warehouse_id', $accessibleIds)
            ->when($request->warehouse_id && in_array((int)$request->warehouse_id, $accessibleIds),
                fn($q) => $q->where('warehouse_id', $request->warehouse_id));
        $baseOut = StockMovement::whereIn('movement_type', ['sale','return_out','adjustment_out'])
            ->whereIn('warehouse_id', $accessibleIds)
            ->when($request->warehouse_id && in_array((int)$request->warehouse_id, $accessibleIds),
                fn($q) => $q->where('warehouse_id', $request->warehouse_id));

        $totalIn  = $baseIn->count();
        $totalOut = $baseOut->count();

        $warehouses = auth()->user()->accessibleWarehouses()->get();

        return view('inventory.history.index', compact('movements', 'movementTypes', 'totalIn', 'totalOut', 'warehouses'));
    }
}
