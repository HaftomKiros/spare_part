<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\SparePart;
use App\Models\StockMovement;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = StockMovement::with('user', 'vehicleModel.vehicleType', 'sparePart.category', 'warehouse')
            ->whereIn('movement_type', ['opening', 'purchase', 'return_in', 'adjustment_in'])
            ->whereIn('warehouse_id', $accessibleIds); // always scope

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('vehicleModel', fn($m) => $m->where('model_name', 'like', "%{$request->search}%"))
                  ->orWhereHas('sparePart', fn($s) => $s->where('name', 'like', "%{$request->search}%")
                                                          ->orWhere('part_number', 'like', "%{$request->search}%"));
            });
        }
        if ($request->type) {
            $query->where('movement_type', $request->type);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->warehouse_id && in_array((int)$request->warehouse_id, $accessibleIds)) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $warehouses = auth()->user()->accessibleWarehouses()->get();
        $movements = $query->latest()->paginate(20)->withQueryString();
        return view('inventory.stock-in.index', compact('movements', 'warehouses'));
    }

    public function create()
    {
        $vehicleTypes     = VehicleType::active()->with('activeVehicleModels.stock')->get();
        $categories       = PartCategory::active()->with('spareParts.unit')->orderBy('name')->get();
        $warehouses       = auth()->user()->accessibleWarehouses()->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();
        return view('inventory.stock-in.create', compact('vehicleTypes', 'categories', 'warehouses', 'defaultWarehouse'));
    }

    /**
     * AJAX: get per-warehouse stock for an item
     */
    public function warehouseStock(Request $request)
    {
        $warehouseId = (int) $request->warehouse_id;
        $itemType    = $request->item_type;
        $itemId      = (int) $request->item_id;

        if (!$warehouseId || !$itemType || !$itemId) {
            return response()->json(['stock' => 0]);
        }

        if ($itemType === 'spare_part') {
            $stock = DB::table('warehouse_spare_part_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('spare_part_id', $itemId)
                ->value('current_stock') ?? 0;
        } else {
            $stock = DB::table('warehouse_vehicle_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('vehicle_model_id', $itemId)
                ->value('current_stock') ?? 0;
        }

        return response()->json(['stock' => (int) $stock]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_type'    => 'required|in:vehicle,spare_part',
            'item_id'      => 'required|integer',
            'quantity'     => 'required|integer|min:1',
            'unit_cost'    => 'nullable|numeric|min:0',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $itemType    = $request->item_type;
            $quantity    = (int) $request->quantity;
            $unitCost    = (float) ($request->unit_cost ?? 0);
            $notes       = $request->notes;
            $userId      = auth()->id();
            $warehouseId = $request->warehouse_id ?: \App\Models\Warehouse::getDefault()?->id;

            if ($itemType === 'vehicle') {
                $model = VehicleModel::findOrFail($request->item_id);
                $this->stockService->increaseVehicleStock(
                    $model, $quantity, 'adjustment_in', $userId, $unitCost, null, null, $notes, $warehouseId
                );
                $name = $model->full_name;
            } else {
                $part = SparePart::findOrFail($request->item_id);
                $this->stockService->increasePartStock(
                    $part, $quantity, 'adjustment_in', $userId, $unitCost, null, null, $notes, $warehouseId
                );
                $name = $part->name;
            }

            DB::commit();
            return redirect()->route('inventory.stock-in.index')
                ->with('success', "Stock added: +{$quantity} units of '{$name}'.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add stock: ' . $e->getMessage())->withInput();
        }
    }
}
