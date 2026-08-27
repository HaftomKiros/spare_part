<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        // sale_returns has no warehouse_id — scope via the related sale's warehouse
        $query = SaleReturn::with('sale', 'customer', 'user')
            ->whereHas('sale', fn($q) => $q->whereIn('warehouse_id', $accessibleIds));

        // Non-admins only see returns they processed themselves
        if (! $user->seesAllUsers()) {
            $query->where('user_id', $user->id);
        }

        if ($request->search) {
            $query->where('return_number', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"))
                  ->orWhereHas('sale', fn($s) => $s->where('invoice_number', 'like', "%{$request->search}%"));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('return_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        $returns = $query->latest()->paginate(20)->withQueryString();
        return view('sales.returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $user          = auth()->user();
        $accessibleIds = $user->accessibleWarehouseIds();

        $saleId = $request->get('sale_id');
        $sale   = $saleId ? Sale::with('items.vehicleModel', 'items.sparePart.unit', 'customer')->findOrFail($saleId) : null;

        $salesQuery = Sale::completed()->with('customer')
            ->whereIn('warehouse_id', $accessibleIds);
        if (! $user->seesAllUsers()) {
            $salesQuery->where('user_id', $user->id);
        }
        $sales  = $salesQuery->latest()->limit(50)->get();
        $number = SaleReturn::generateNumber();

        return view('sales.returns.create', compact('sale', 'sales', 'number'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id'       => 'required|exists:sales,id',
            'return_date'   => 'required|date',
            'return_type'   => 'required|in:refund,exchange,credit',
            'reason'        => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.sale_item_id'  => 'required|integer',
            'items.*.item_type'     => 'required|in:vehicle,spare_part',
            'items.*.item_id'       => 'required|integer',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $sale        = Sale::findOrFail($request->sale_id);
            $totalAmount = collect($request->items)
                ->sum(fn($i) => $i['quantity'] * $i['unit_price']);

            $return = SaleReturn::create([
                'return_number' => SaleReturn::generateNumber(),
                'sale_id'       => $sale->id,
                'customer_id'   => $sale->customer_id,
                'user_id'       => auth()->id(),
                'return_date'   => $request->return_date,
                'total_amount'  => $totalAmount,
                'return_type'   => $request->return_type,
                'reason'        => $request->reason,
                'status'        => 'approved',
            ]);

            foreach ($request->items as $row) {
                // Skip if required fields missing (safety guard)
                if (empty($row['item_id']) || empty($row['item_type'])) continue;

                SaleReturnItem::create([
                    'sale_return_id'   => $return->id,
                    'item_type'        => $row['item_type'],
                    'vehicle_model_id' => $row['item_type'] === 'vehicle'    ? $row['item_id'] : null,
                    'spare_part_id'    => $row['item_type'] === 'spare_part' ? $row['item_id'] : null,
                    'quantity'         => $row['quantity'],
                    'unit_price'       => $row['unit_price'],
                    'total'            => $row['quantity'] * $row['unit_price'],
                ]);

                // Return stock back to the same warehouse the sale came from
                $warehouseId = $sale->warehouse_id ?? \App\Models\Warehouse::getDefault()?->id;
                $qty = (int) $row['quantity'];
                if ($row['item_type'] === 'vehicle') {
                    $model = \App\Models\VehicleModel::findOrFail($row['item_id']);
                    $this->stockService->increaseVehicleStock(
                        $model, $qty, 'return_in', auth()->id(), $row['unit_price'],
                        SaleReturn::class, $return->id, "Return #{$return->return_number}", $warehouseId
                    );
                } else {
                    $part = \App\Models\SparePart::findOrFail($row['item_id']);
                    $this->stockService->increasePartStock(
                        $part, $qty, 'return_in', auth()->id(), $row['unit_price'],
                        SaleReturn::class, $return->id, "Return #{$return->return_number}", $warehouseId
                    );
                }

                // Reduce total_sold on the linked purchase_item so the batch
                // becomes available again for future sales.
                $saleItemId = $row['sale_item_id'] ?? null;
                if ($saleItemId) {
                    $purchaseItemId = DB::table('sale_items')
                        ->where('id', $saleItemId)
                        ->value('purchase_item_id');

                    if ($purchaseItemId) {
                        DB::table('purchase_items')
                            ->where('id', $purchaseItemId)
                            ->update([
                                'total_sold' => DB::raw("GREATEST(0, total_sold - {$qty})"),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('sales.returns.index')
                ->with('success', "Return #{$return->return_number} processed successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process return: ' . $e->getMessage())->withInput();
        }
    }

    public function show(SaleReturn $return)
    {
        $return->load('sale', 'customer', 'user', 'items.vehicleModel.vehicleType', 'items.sparePart.category');
        return view('sales.returns.show', compact('return'));
    }
}
