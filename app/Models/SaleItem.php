<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'item_type',
        'vehicle_model_id',
        'spare_part_id',
        'quantity',
        'unit_price',
        'discount',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    public function getProfitAttribute(): float
    {
        $cost = 0;
        if ($this->item_type === 'vehicle') {
            $cost = $this->vehicleModel?->buying_price ?? 0;
        } else {
            $cost = $this->sparePart?->buying_price ?? 0;
        }
        return ($this->unit_price - $cost) * $this->quantity;
    }
}
