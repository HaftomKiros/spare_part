<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleStock extends Model
{
    protected $fillable = [
        'vehicle_model_id',
        'current_stock',
        'reorder_level',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function isLow(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) return 'out_of_stock';
        if ($this->isLow())        return 'low';
        return 'in_stock';
    }

    public function getStockStatusLabelAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low'          => 'Low Stock',
            default        => 'In Stock',
        };
    }
}
