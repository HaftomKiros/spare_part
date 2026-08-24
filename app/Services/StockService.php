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
            }
        }
    }
}
