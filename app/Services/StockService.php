<?php

namespace App\Services;

use App\Models\SparePart;
use App\Models\StockMovement;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get or create per-warehouse stock record for a spare part.
     */
    private function partWarehouseStock(int $warehouseId, int $partId): object
    {
        $row = DB::table('warehouse_spare_part_stock')
            ->where('warehouse_id', $warehouseId)
            ->where('spare_part_id', $partId)
            ->first();

        if (!$row) {
            DB::table('warehouse_spare_part_stock')->insert([
                'warehouse_id'  => $warehouseId,
                'spare_part_id' => $partId,
                'current_stock' => 0,
                'reorder_level' => 5,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $row = DB::table('warehouse_spare_part_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('spare_part_id', $partId)
                ->first();
        }

        return $row;
    }

    /**
     * Get or create per-warehouse stock record for a vehicle model.
     */
    private function vehicleWarehouseStock(int $warehouseId, int $modelId): object
    {
        $row = DB::table('warehouse_vehicle_stock')
            ->where('warehouse_id', $warehouseId)
            ->where('vehicle_model_id', $modelId)
            ->first();

        if (!$row) {
            DB::table('warehouse_vehicle_stock')->insert([
                'warehouse_id'     => $warehouseId,
                'vehicle_model_id' => $modelId,
                'current_stock'    => 0,
                'reorder_level'    => 2,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $row = DB::table('warehouse_vehicle_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('vehicle_model_id', $modelId)
                ->first();
        }

        return $row;
    }

    /**
     * Increase spare part stock in a specific warehouse.
     */
    public function increasePartStock(
        SparePart $part,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $warehouseId = null
    ): void {
        DB::transaction(function () use ($part, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes, $warehouseId) {
            $warehouseId = $warehouseId ?? Warehouse::getDefault()?->id;

            // Update per-warehouse stock
            if ($warehouseId) {
                $row    = $this->partWarehouseStock($warehouseId, $part->id);
                $before = $row->current_stock;
                $after  = $before + $quantity;
                DB::table('warehouse_spare_part_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('spare_part_id', $part->id)
                    ->update(['current_stock' => $after, 'updated_at' => now()]);
            } else {
                $before = $part->current_stock;
                $after  = $before + $quantity;
            }

            // Update global stock on spare_parts table
            $part->increment('current_stock', $quantity);

            StockMovement::create([
                'item_type'       => 'spare_part',
                'spare_part_id'   => $part->id,
                'movement_type'   => $movementType,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $unitCost,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'user_id'         => $userId,
                'warehouse_id'    => $warehouseId,
                'notes'           => $notes,
            ]);
        });
    }

    /**
     * Decrease spare part stock in a specific warehouse.
     */
    public function decreasePartStock(
        SparePart $part,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $warehouseId = null
    ): void {
        DB::transaction(function () use ($part, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes, $warehouseId) {
            $warehouseId = $warehouseId ?? Warehouse::getDefault()?->id;

            if ($warehouseId) {
                $row    = $this->partWarehouseStock($warehouseId, $part->id);
                $before = $row->current_stock;
                $after  = max(0, $before - $quantity);
                DB::table('warehouse_spare_part_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('spare_part_id', $part->id)
                    ->update(['current_stock' => $after, 'updated_at' => now()]);
            } else {
                $before = $part->current_stock;
                $after  = max(0, $before - $quantity);
            }

            $part->decrement('current_stock', min($quantity, $part->current_stock));

            StockMovement::create([
                'item_type'       => 'spare_part',
                'spare_part_id'   => $part->id,
                'movement_type'   => $movementType,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $unitCost,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'user_id'         => $userId,
                'warehouse_id'    => $warehouseId,
                'notes'           => $notes,
            ]);
        });
    }

    /**
     * Increase vehicle stock in a specific warehouse.
     */
    public function increaseVehicleStock(
        VehicleModel $model,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $warehouseId = null
    ): void {
        DB::transaction(function () use ($model, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes, $warehouseId) {
            $warehouseId = $warehouseId ?? Warehouse::getDefault()?->id;

            if ($warehouseId) {
                $row    = $this->vehicleWarehouseStock($warehouseId, $model->id);
                $before = $row->current_stock;
                $after  = $before + $quantity;
                DB::table('warehouse_vehicle_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('vehicle_model_id', $model->id)
                    ->update(['current_stock' => $after, 'updated_at' => now()]);
            } else {
                $stock  = $model->stock;
                $before = $stock?->current_stock ?? 0;
                $after  = $before + $quantity;
                $stock?->update(['current_stock' => $after]);
            }

            // Also update global vehicle_stocks
            \App\Models\VehicleStock::updateOrCreate(
                ['vehicle_model_id' => $model->id],
                ['current_stock' => DB::raw("current_stock + $quantity")]
            );

            StockMovement::create([
                'item_type'        => 'vehicle',
                'vehicle_model_id' => $model->id,
                'movement_type'    => $movementType,
                'quantity'         => $quantity,
                'quantity_before'  => $before,
                'quantity_after'   => $after,
                'unit_cost'        => $unitCost,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'warehouse_id'     => $warehouseId,
                'notes'            => $notes,
            ]);
        });
    }

    /**
     * Decrease vehicle stock in a specific warehouse.
     */
    public function decreaseVehicleStock(
        VehicleModel $model,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $warehouseId = null
    ): void {
        DB::transaction(function () use ($model, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes, $warehouseId) {
            $warehouseId = $warehouseId ?? Warehouse::getDefault()?->id;

            if ($warehouseId) {
                $row    = $this->vehicleWarehouseStock($warehouseId, $model->id);
                $before = $row->current_stock;
                $after  = max(0, $before - $quantity);
                DB::table('warehouse_vehicle_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('vehicle_model_id', $model->id)
                    ->update(['current_stock' => $after, 'updated_at' => now()]);
            } else {
                $stock  = $model->stock;
                $before = $stock?->current_stock ?? 0;
                $after  = max(0, $before - $quantity);
                $stock?->update(['current_stock' => $after]);
            }

            // Also update global vehicle_stocks
            if ($model->stock) {
                $model->stock->decrement('current_stock', min($quantity, $model->stock->current_stock));
            }

            StockMovement::create([
                'item_type'        => 'vehicle',
                'vehicle_model_id' => $model->id,
                'movement_type'    => $movementType,
                'quantity'         => $quantity,
                'quantity_before'  => $before,
                'quantity_after'   => $after,
                'unit_cost'        => $unitCost,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'warehouse_id'     => $warehouseId,
                'notes'            => $notes,
            ]);
        });
    }

    /**
     * Process stock for a completed purchase (add to warehouse).
     */
    public function processPurchaseStock(\App\Models\Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            if ($item->item_type === 'vehicle' && $item->vehicleModel) {
                $this->increaseVehicleStock(
                    $item->vehicleModel, $item->quantity, 'purchase',
                    $purchase->user_id, $item->unit_price,
                    \App\Models\Purchase::class, $purchase->id,
                    "Purchase #{$purchase->purchase_number}",
                    $purchase->warehouse_id
                );
            } elseif ($item->item_type === 'spare_part' && $item->sparePart) {
                $this->increasePartStock(
                    $item->sparePart, $item->quantity, 'purchase',
                    $purchase->user_id, $item->unit_price,
                    \App\Models\Purchase::class, $purchase->id,
                    "Purchase #{$purchase->purchase_number}",
                    $purchase->warehouse_id
                );
            }
        }
    }

    /**
     * Process stock for a completed sale (deduct from warehouse).
     * Also increments total_sold on matching purchase_items using FIFO order
     * and stamps the matched purchase_item_id onto the sale_item row.
     *
     * If the sale_item already has a purchase_item_id set (user picked a
     * specific batch on the form), we honour that instead of FIFO.
     */
    public function processSaleStock(\App\Models\Sale $sale): void
    {
        foreach ($sale->items as $item) {
            if ($item->item_type === 'vehicle' && $item->vehicleModel) {
                $this->decreaseVehicleStock(
                    $item->vehicleModel, $item->quantity, 'sale',
                    $sale->user_id, $item->unit_price,
                    \App\Models\Sale::class, $sale->id,
                    "Sale #{$sale->invoice_number}",
                    $sale->warehouse_id
                );
            } elseif ($item->item_type === 'spare_part' && $item->sparePart) {
                $this->decreasePartStock(
                    $item->sparePart, $item->quantity, 'sale',
                    $sale->user_id, $item->unit_price,
                    \App\Models\Sale::class, $sale->id,
                    "Sale #{$sale->invoice_number}",
                    $sale->warehouse_id
                );
            } else {
                continue;
            }

            // If user picked a specific batch, increment that batch directly.
            // Otherwise fall back to FIFO auto-assign.
            if ($item->purchase_item_id) {
                $this->incrementSpecificPurchaseItem($item->purchase_item_id, $item->quantity);
            } else {
                $column         = $item->item_type === 'vehicle' ? 'vehicle_model_id' : 'spare_part_id';
                $itemId         = $item->item_type === 'vehicle' ? $item->vehicle_model_id : $item->spare_part_id;
                $purchaseItemId = $this->incrementPurchaseItemSold(
                    $item->item_type, $itemId, $sale->warehouse_id, $item->quantity
                );

                // Stamp the FIFO-matched purchase_item_id onto the sale_item row
                if ($purchaseItemId) {
                    DB::table('sale_items')
                        ->where('id', $item->id)
                        ->update(['purchase_item_id' => $purchaseItemId, 'updated_at' => now()]);
                }
            }
        }
    }

    /**
     * Increment total_sold on a specific purchase_item by the given quantity.
     * Used when the user explicitly picks a batch on the sale form.
     */
    private function incrementSpecificPurchaseItem(int $purchaseItemId, int $qty): void
    {
        DB::table('purchase_items')
            ->where('id', $purchaseItemId)
            ->update([
                'total_sold' => DB::raw("total_sold + {$qty}"),
                'updated_at' => now(),
            ]);
    }

    /**
     * Distribute a sold quantity across purchase_items using FIFO.
     *
     * Returns the purchase_item_id of the first (oldest) batch consumed —
     * used to link the sale_item back to its source purchase.
     *
     * NOTE: Transfer-stub batches (is_transfer = true) are included in FIFO
     * because they represent real stock at this warehouse. They are ordered
     * after real purchases of the same date so genuine PO batches are
     * consumed first.
     */
    private function incrementPurchaseItemSold(
        string $itemType,
        int $itemId,
        ?int $warehouseId,
        int $qtySold
    ): ?int {
        if ($qtySold <= 0) return null;

        $column = $itemType === 'vehicle' ? 'vehicle_model_id' : 'spare_part_id';

        // Load purchase items oldest-first that still have remaining qty.
        // Transfer stubs (is_transfer=1) sort after real purchases on the same
        // date so we consume genuine PO stock first, then transfer stock.
        $purchaseItems = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', $itemType)
            ->where("pi.{$column}", $itemId)
            ->where('p.status', 'received')
            ->when($warehouseId, fn($q) => $q->where('p.warehouse_id', $warehouseId))
            ->whereRaw('pi.quantity > pi.total_sold')
            ->orderBy('p.purchase_date')
            ->orderByRaw('pi.is_transfer ASC')  // real purchases (0) before transfer stubs (1)
            ->orderBy('pi.id')
            ->select('pi.id', 'pi.quantity', 'pi.total_sold')
            ->get();

        $remaining      = $qtySold;
        $firstMatchedId = null;

        foreach ($purchaseItems as $pi) {
            if ($remaining <= 0) break;

            $available = $pi->quantity - $pi->total_sold;
            $toAdd     = min($remaining, $available);

            DB::table('purchase_items')
                ->where('id', $pi->id)
                ->update([
                    'total_sold' => $pi->total_sold + $toAdd,
                    'updated_at' => now(),
                ]);

            // Remember the first (FIFO) batch — this is the PO linked to this sale line
            if ($firstMatchedId === null) {
                $firstMatchedId = $pi->id;
            }

            $remaining -= $toAdd;
        }

        return $firstMatchedId;
    }

    // ──────────────────────────────────────────────────────────────
    // Stock Value Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Returns a map of spare_part_id => stock_value for each part.
     *
     * stock_value = SUM((quantity - total_sold) * unit_price)
     *              from received purchase_items  (unsold qty × purchase price)
     */
    public static function partsStockValueMap(array $partIds, array $warehouseIds = []): array
    {
        if (empty($partIds)) return [];

        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'spare_part')
            ->whereIn('pi.spare_part_id', $partIds)
            ->where('p.status', 'received')
            ->where('p.purchase_type', 'purchase')   // exclude transfer stubs — avoid double-counting
            ->when(!empty($warehouseIds), fn($q) => $q->whereIn('p.warehouse_id', $warehouseIds))
            ->whereRaw('pi.quantity > pi.total_sold')
            ->selectRaw('pi.spare_part_id, SUM((pi.quantity - pi.total_sold) * pi.unit_price) as stock_value')
            ->groupBy('pi.spare_part_id')
            ->pluck('stock_value', 'spare_part_id');

        $map = [];
        foreach ($partIds as $id) {
            $map[$id] = max(0.0, (float) ($rows[$id] ?? 0));
        }

        return $map;
    }

    /**
     * Returns a map of vehicle_model_id => stock_value for each vehicle model.
     *
     * stock_value = SUM((quantity - total_sold) * unit_price)
     *              from received purchase_items  (unsold qty × purchase price)
     */
    public static function vehiclesStockValueMap(array $vehicleIds, array $warehouseIds = []): array
    {
        if (empty($vehicleIds)) return [];

        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'vehicle')
            ->whereIn('pi.vehicle_model_id', $vehicleIds)
            ->where('p.status', 'received')
            ->where('p.purchase_type', 'purchase')   // exclude transfer stubs — avoid double-counting
            ->when(!empty($warehouseIds), fn($q) => $q->whereIn('p.warehouse_id', $warehouseIds))
            ->whereRaw('pi.quantity > pi.total_sold')
            ->selectRaw('pi.vehicle_model_id, SUM((pi.quantity - pi.total_sold) * pi.unit_price) as stock_value')
            ->groupBy('pi.vehicle_model_id')
            ->pluck('stock_value', 'vehicle_model_id');

        $map = [];
        foreach ($vehicleIds as $id) {
            $map[$id] = max(0.0, (float) ($rows[$id] ?? 0));
        }

        return $map;
    }

    /**
     * Returns a map of spare_part_id => last_purchase_unit_price
     * built from purchase_items in a single query.
     * Parts that have never been purchased map to 0.
     */
    public static function lastPurchasePriceMap(array $partIds = []): array
    {
        if (empty($partIds)) {
            $partIds = DB::table('purchase_items')
                ->whereNotNull('spare_part_id')
                ->distinct()
                ->pluck('spare_part_id')
                ->toArray();
        }

        if (empty($partIds)) {
            return [];
        }

        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->whereIn('pi.spare_part_id', $partIds)
            ->whereNotNull('pi.spare_part_id')
            ->select('pi.spare_part_id', 'pi.unit_price', 'p.purchase_date')
            ->orderByDesc('p.purchase_date')
            ->get()
            ->unique('spare_part_id');

        $map = [];
        foreach ($rows as $row) {
            $map[$row->spare_part_id] = (float) $row->unit_price;
        }

        return $map;
    }

    /**
     * Returns a map of vehicle_model_id => last_purchase_unit_price.
     * Falls back to vehicle_models.buying_price if no purchase exists.
     */
    public static function lastVehiclePriceMap(array $vehicleIds = []): array
    {
        if (empty($vehicleIds)) {
            $vehicleIds = DB::table('warehouse_vehicle_stock')
                ->distinct()->pluck('vehicle_model_id')->toArray();
        }

        if (empty($vehicleIds)) {
            return [];
        }

        // Last purchase price per vehicle model
        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->whereIn('pi.vehicle_model_id', $vehicleIds)
            ->whereNotNull('pi.vehicle_model_id')
            ->select('pi.vehicle_model_id', 'pi.unit_price', 'p.purchase_date')
            ->orderByDesc('p.purchase_date')
            ->get()
            ->unique('vehicle_model_id');

        $map = [];
        foreach ($rows as $row) {
            $map[$row->vehicle_model_id] = (float) $row->unit_price;
        }

        // Fall back to catalog buying_price for vehicles with no purchase record
        $missing = array_diff($vehicleIds, array_keys($map));
        if (!empty($missing)) {
            DB::table('vehicle_models')
                ->whereIn('id', $missing)
                ->get(['id', 'buying_price'])
                ->each(function ($vm) use (&$map) {
                    $map[$vm->id] = (float) $vm->buying_price;
                });
        }

        return $map;
    }

    /**
     * Calculate total spare-parts stock value for given warehouse IDs.
     *
     * Formula: SUM((quantity - total_sold) * unit_price)
     * = unsold quantity × purchase price per batch
     */
    public static function partsStockValue(array $warehouseIds = []): float
    {
        $query = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'spare_part')
            ->whereNotNull('pi.spare_part_id')
            ->where('p.status', 'received')
            ->where('p.purchase_type', 'purchase')   // exclude transfer stubs — avoid double-counting
            ->whereRaw('pi.quantity > pi.total_sold');

        if (!empty($warehouseIds)) {
            $query->whereIn('p.warehouse_id', $warehouseIds);
        }

        return max(0.0, (float) $query->sum(DB::raw('(pi.quantity - pi.total_sold) * pi.unit_price')));
    }

    /**
     * Calculate total vehicle stock value for given warehouse IDs.
     *
     * Formula: SUM((quantity - total_sold) * unit_price)
     * = unsold quantity × purchase price per batch
     */
    public static function vehiclesStockValue(array $warehouseIds = []): float
    {
        $query = DB::table('purchase_items as pi')
            ->join('purchases as p', 'pi.purchase_id', '=', 'p.id')
            ->where('pi.item_type', 'vehicle')
            ->whereNotNull('pi.vehicle_model_id')
            ->where('p.status', 'received')
            ->where('p.purchase_type', 'purchase')   // exclude transfer stubs — avoid double-counting
            ->whereRaw('pi.quantity > pi.total_sold');

        if (!empty($warehouseIds)) {
            $query->whereIn('p.warehouse_id', $warehouseIds);
        }

        return max(0.0, (float) $query->sum(DB::raw('(pi.quantity - pi.total_sold) * pi.unit_price')));
    }
}
