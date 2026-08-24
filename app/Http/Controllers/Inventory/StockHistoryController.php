<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('user', 'vehicleModel.vehicleType', 'sparePart.category');

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

        // Summary totals
        $totalIn  = StockMovement::whereIn('movement_type', ['purchase','return_in','adjustment_in','opening'])->count();
        $totalOut = StockMovement::whereIn('movement_type', ['sale','return_out','adjustment_out'])->count();

        return view('inventory.history.index', compact('movements', 'movementTypes', 'totalIn', 'totalOut'));
    }
}
