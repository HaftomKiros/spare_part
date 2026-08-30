<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VehicleModel extends Model
{
    protected $fillable = [
        'vehicle_type_id',
        'brand',
        'model_name',
        'model_code',
        'year',
        'engine_cc',
        'selling_price_min',
        'selling_price_max',
        'buying_price',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'selling_price_min' => 'decimal:2',
        'selling_price_max' => 'decimal:2',
        'buying_price'      => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(VehicleStock::class);
    }

    public function spareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'spare_part_vehicle_model');
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
        return $this->hasMany(StockMovement::class)->where('item_type', 'vehicle');
    }

    public function warehouses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_vehicle_stock')
            ->withPivot('current_stock', 'reorder_level')
            ->withTimestamps();
    }

    public function getStockInWarehouse(int $warehouseId): int
    {
        return \DB::table('warehouse_vehicle_stock')
            ->where('warehouse_id', $warehouseId)
            ->where('vehicle_model_id', $this->id)
            ->value('current_stock') ?? 0;
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model_name}" . ($this->model_code ? " ({$this->model_code})" : '');
    }

    public function getCurrentStockAttribute(): int
    {
        return $this->stock?->current_stock ?? 0;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->buying_price <= 0) return 0;
        return round((($this->selling_price_max - $this->buying_price) / $this->buying_price) * 100, 2);
    }

    public function isLowStock(): bool
    {
        $stock = $this->stock;
        return $stock && $stock->current_stock <= $stock->reorder_level;
    }
}
