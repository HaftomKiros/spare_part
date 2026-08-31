<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SparePart;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $query = StockAdjustment::with('user', 'warehouse')->withCount('items')
            ->whereIn('warehouse_id', $accessibleIds);

        // Inventory adjustments: warehouse only — all users in the same warehouse see all adjustments

        if ($request->search) {
            $query->where('adjustment_number', 'like', "%{$request->search}%")
                  ->orWhere('reason', 'like', "%{$request->search}%");
        }
        if ($request->type) {
            $query->where('adjustment_type', $request->type);
        }
        if ($request->date_from) {
            $query->whereDate('adjustment_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('adjustment_date', '<=', $request->date_to);
        }

        $adjustments = $query->latest()->paginate(15)->withQueryString();
        return view('inventory.adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $vehicleTypes     = VehicleType::active()->with('activeVehicleModels.stock')->get();
        $categories       = PartCategory::active()->with('spareParts.unit')->orderBy('name')->get();
        $number           = StockAdjustment::generateNumber();
        $warehouses       = auth()->user()->accessibleWarehouses()->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();
        return view('inventory.adjustments.create', compact('vehicleTypes', 'categories', 'number', 'warehouses', 'defaultWarehouse'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date'    => 'required|date',
            'adjustment_type'    => 'required|in:increase,decrease,recount',
            'reason'             => 'required|string|max:500',
            'warehouse_id'       => 'nullable|exists:warehouses,id',
            'items'              => 'required|array|min:1',
            'items.*.item_type'  => 'required|in:vehicle,spare_part',
            'items.*.item_id'    => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.notes'      => 'nullable|string|max:300',
        ]);

        DB::beginTransaction();
        try {
            $warehouseId = $request->warehouse_id ?: \App\Models\Warehouse::getDefault()?->id;

            $adjustment = StockAdjustment::create([
                'adjustment_number' => StockAdjustment::generateNumber(),
                'user_id'           => auth()->id(),
                'warehouse_id'      => $warehouseId,
                'adjustment_date'   => $request->adjustment_date,
                'adjustment_type'   => $request->adjustment_type,
                'reason'            => $request->reason,
                'status'            => 'approved',
            ]);

            $isIncrease = $request->adjustment_type === 'increase';

            foreach ($request->items as $row) {
                $qty      = (int) $row['quantity'];
                $itemType = $row['item_type'];
                $notes    = $row['notes'] ?? null;

                if ($itemType === 'vehicle') {
                    $model  = VehicleModel::findOrFail($row['item_id']);
                    $before = $model->stock?->current_stock ?? 0;
                    $after  = $isIncrease ? $before + $qty : max(0, $before - $qty);

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'item_type'           => 'vehicle',
                        'vehicle_model_id'    => $model->id,
                        'quantity_before'     => $before,
                        'quantity_adjusted'   => $isIncrease ? $qty : -$qty,
                        'quantity_after'      => $after,
                        'notes'               => $notes,
                    ]);

                    $movementType = $isIncrease ? 'adjustment_in' : 'adjustment_out';
                    if ($isIncrease) {
                        $this->stockService->increaseVehicleStock($model, $qty, $movementType, auth()->id(), 0,
                            StockAdjustment::class, $adjustment->id, $request->reason, $warehouseId);
                        // Create synthetic purchase record so FIFO batch accounting stays balanced
                        $syntheticPurchase = Purchase::create([
                            'purchase_number' => 'ADJ-' . $adjustment->adjustment_number . '-V' . $model->id,
                            'supplier_id'     => DB::table('suppliers')->orderBy('id')->value('id') ?? 1,
                            'user_id'         => auth()->id(),
                            'warehouse_id'    => $warehouseId,
                            'purchase_date'   => now()->toDateString(),
                            'subtotal'        => $model->buying_price * $qty,
                            'discount'        => 0, 'tax' => 0,
                            'total'           => $model->buying_price * $qty,
                            'paid_amount'     => $model->buying_price * $qty,
                            'balance'         => 0,
                            'payment_status'  => 'paid',
                            'status'          => 'received',
                            'notes'           => 'Auto-created from stock adjustment #' . $adjustment->adjustment_number,
                            'purchase_type'   => 'adjustment',
                        ]);
                        PurchaseItem::create([
                            'purchase_id'      => $syntheticPurchase->id,
                            'item_type'        => 'vehicle',
                            'vehicle_model_id' => $model->id,
                            'spare_part_id'    => null,
                            'quantity'         => $qty,
                            'unit_price'       => $model->buying_price,
                            'discount'         => 0,
                            'total'            => $model->buying_price * $qty,
                            'total_sold'       => 0,
                        ]);
                    } else {
                        $this->stockService->decreaseVehicleStock($model, $qty, $movementType, auth()->id(), 0,
                            StockAdjustment::class, $adjustment->id, $request->reason, $warehouseId);
                    }

                } else {
                    $part   = SparePart::findOrFail($row['item_id']);
                    $before = $part->current_stock;
                    $after  = $isIncrease ? $before + $qty : max(0, $before - $qty);

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'item_type'           => 'spare_part',
                        'spare_part_id'       => $part->id,
                        'quantity_before'     => $before,
                        'quantity_adjusted'   => $isIncrease ? $qty : -$qty,
                        'quantity_after'      => $after,
                        'notes'               => $notes,
                    ]);

                    $movementType = $isIncrease ? 'adjustment_in' : 'adjustment_out';
                    if ($isIncrease) {
                        $this->stockService->increasePartStock($part, $qty, $movementType, auth()->id(), 0,
                            StockAdjustment::class, $adjustment->id, $request->reason, $warehouseId);
                        // Create synthetic purchase record so FIFO batch accounting stays balanced
                        $costPrice = $part->buying_price > 0 ? $part->buying_price : 0;
                        $syntheticPurchase = Purchase::create([
                            'purchase_number' => 'ADJ-' . $adjustment->adjustment_number . '-P' . $part->id,
                            'supplier_id'     => DB::table('suppliers')->orderBy('id')->value('id') ?? 1,
                            'user_id'         => auth()->id(),
                            'warehouse_id'    => $warehouseId,
                            'purchase_date'   => now()->toDateString(),
                            'subtotal'        => $costPrice * $qty,
                            'discount'        => 0, 'tax' => 0,
                            'total'           => $costPrice * $qty,
                            'paid_amount'     => $costPrice * $qty,
                            'balance'         => 0,
                            'payment_status'  => 'paid',
                            'status'          => 'received',
                            'notes'           => 'Auto-created from stock adjustment #' . $adjustment->adjustment_number,
                            'purchase_type'   => 'adjustment',
                        ]);
                        PurchaseItem::create([
                            'purchase_id'      => $syntheticPurchase->id,
                            'item_type'        => 'spare_part',
                            'vehicle_model_id' => null,
                            'spare_part_id'    => $part->id,
                            'quantity'         => $qty,
                            'unit_price'       => $costPrice,
                            'discount'         => 0,
                            'total'            => $costPrice * $qty,
                            'total_sold'       => 0,
                        ]);
                    } else {
                        $this->stockService->decreasePartStock($part, $qty, $movementType, auth()->id(), 0,
                            StockAdjustment::class, $adjustment->id, $request->reason, $warehouseId);
                    }
                }
            }

            DB::commit();
            return redirect()->route('inventory.adjustments.index')
                ->with('success', "Adjustment #{$adjustment->adjustment_number} saved successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save adjustment: ' . $e->getMessage())->withInput();
        }
    }

    public function show(StockAdjustment $adjustment)
    {
        $adjustment->load('user', 'items.vehicleModel.vehicleType', 'items.sparePart.category');
        return view('inventory.adjustments.show', compact('adjustment'));
    }
}
