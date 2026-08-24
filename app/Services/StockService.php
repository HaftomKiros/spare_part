<?php

namespace App\Services;

use App\Models\SparePart;
use App\Models\StockMovement;
use App\Models\VehicleModel;
use App\Models\VehicleStock;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Increase stock (purchase, return-in, adjustment, opening).
     */
    public function increaseVehicleStock(
        VehicleModel $model,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($model, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes) {
            $stock = VehicleStock::firstOrCreate(
                ['vehicle_model_id' => $model->id],
                ['current_stock' => 0, 'reorder_level' => 2]
            );

            $before = $stock->current_stock;
            $after  = $before + $quantity;

            $stock->update(['current_stock' => $after]);

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
                'notes'            => $notes,
            ]);
        });
    }

    /**
     * Decrease stock (sale, return-out, adjustment).
     */
    public function decreaseVehicleStock(
        VehicleModel $model,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($model, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes) {
            $stock = VehicleStock::firstOrCreate(
                ['vehicle_model_id' => $model->id],
                ['current_stock' => 0, 'reorder_level' => 2]
            );

            $before = $stock->current_stock;
            $after  = max(0, $before - $quantity);

            $stock->update(['current_stock' => $after]);

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
                'notes'            => $notes,
            ]);
        });
    }

    /**
     * Increase spare part stock.
     */
    public function increasePartStock(
        SparePart $part,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($part, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes) {
            $before = $part->current_stock;
            $after  = $before + $quantity;

            $part->update(['current_stock' => $after]);

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
                'notes'           => $notes,
            ]);
        });
    }

    /**
     * Decrease spare part stock.
     */
    public function decreasePartStock(
        SparePart $part,
        int $quantity,
        string $movementType,
        int $userId,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($part, $quantity, $movementType, $userId, $unitCost, $referenceType, $referenceId, $notes) {
            $before = $part->current_stock;
            $after  = max(0, $before - $quantity);

            $part->update(['current_stock' => $after]);

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
                'notes'           => $notes,
            ]);
        });
    }

    /**
     * Process all stock changes for a completed purchase.
     */
    public function processPurchaseStock(\App\Models\Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            if ($item->item_type === 'vehicle' && $item->vehicleModel) {
                $this->increaseVehicleStock(
                    $item->vehicleModel,
                    $item->quantity,
                    'purchase',
                    $purchase->user_id,
                    $item->unit_price,
                    \App\Models\Purchase::class,
                    $purchase->id,
                    "Purchase #{$purchase->purchase_number}"
                );
            } elseif ($item->item_type === 'spare_part' && $item->sparePart) {
                $this->increasePartStock(
                    $item->sparePart,
                    $item->quantity,
                    'purchase',
                    $purchase->user_id,
                    $item->unit_price,
                    \App\Models\Purchase::class,
                    $purchase->id,
                    "Purchase #{$purchase->purchase_number}"
                );
            }
        }
    }

    /**
     * Process all stock changes for a completed sale.
     */
    public function processSaleStock(\App\Models\Sale $sale): void
    {
        foreach ($sale->items as $item) {
            if ($item->item_type === 'vehicle' && $item->vehicleModel) {
                $this->decreaseVehicleStock(
                    $item->vehicleModel,
                    $item->quantity,
                    'sale',
                    $sale->user_id,
                    $item->unit_price,
                    \App\Models\Sale::class,
                    $sale->id,
                    "Sale #{$sale->invoice_number}"
                );
            } elseif ($item->item_type === 'spare_part' && $item->sparePart) {
                $this->decreasePartStock(
                    $item->sparePart,
                    $item->quantity,
                    'sale',
                    $sale->user_id,
                    $item->unit_price,
                    \App\Models\Sale::class,
                    $sale->id,
                    "Sale #{$sale->invoice_number}"
                );
            }
        }
    }
}
