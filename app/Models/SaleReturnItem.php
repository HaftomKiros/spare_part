<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'item_type',
        'vehicle_model_id',
        'spare_part_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'vehicle') {
            return $this->vehicleModel?->full_name ?? 'N/A';
        }
        return $this->sparePart?->name ?? 'N/A';
    }
}
