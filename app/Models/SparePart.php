<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected $fillable = [
        'part_category_id',
        'unit_id',
        'part_number',
        'oem_number',
        'name',
        'description',
        'buying_price',
        'selling_price',
        'reorder_level',
        'current_stock',
        'location',
        'image',
        'status',
    ];

    protected $casts = [
        'buying_price'  => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'part_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function compatibleVehicles(): BelongsToMany
    {
        return $this->belongsToMany(VehicleModel::class, 'spare_part_vehicle_model');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->where('item_type', 'spare_part');
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function isLowStock(): bool
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
        if ($this->isLowStock())   return 'low';
        return 'in_stock';
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->buying_price <= 0) return 0;
        return round((($this->selling_price - $this->buying_price) / $this->buying_price) * 100, 2);
    }

    public function getProfitAmountAttribute(): float
    {
        return $this->selling_price - $this->buying_price;
    }
}
