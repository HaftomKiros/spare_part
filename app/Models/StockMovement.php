<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'item_type',
        'vehicle_model_id',
        'spare_part_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getMovementTypeLabelAttribute(): string
    {
        return match($this->movement_type) {
            'purchase'       => 'Purchase',
            'sale'           => 'Sale',
            'return_in'      => 'Return (In)',
            'return_out'     => 'Return (Out)',
            'adjustment_in'  => 'Adjustment (+)',
            'adjustment_out' => 'Adjustment (-)',
            'opening'        => 'Opening Stock',
            default          => ucfirst($this->movement_type),
        };
    }

    public function getMovementTypeBadgeAttribute(): string
    {
        return match($this->movement_type) {
            'purchase', 'return_in', 'adjustment_in', 'opening' => 'success',
            'sale', 'return_out', 'adjustment_out'               => 'danger',
            default                                               => 'secondary',
        };
    }

    public function isInward(): bool
    {
        return in_array($this->movement_type, ['purchase', 'return_in', 'adjustment_in', 'opening']);
    }
}
