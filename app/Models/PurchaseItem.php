<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'item_type',
        'vehicle_model_id',
        'spare_part_id',
        'quantity',
        'total_sold',
        'unit_price',
        'discount',
        'total',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'discount'    => 'decimal:2',
        'total'       => 'decimal:2',
        'total_sold'  => 'integer',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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

    /**
     * Remaining quantity = purchased qty minus total sold.
     */
    public function getRemainingQtyAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->total_sold);
    }
}
