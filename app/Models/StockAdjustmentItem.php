<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'item_type',
        'vehicle_model_id',
        'spare_part_id',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'notes',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'vehicle') {
            return $this->vehicleModel?->full_name ?? 'N/A';
        }
        return $this->sparePart?->name ?? 'N/A';
    }
}
