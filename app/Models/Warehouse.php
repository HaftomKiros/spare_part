<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code', 'name', 'city', 'address',
        'phone', 'manager', 'status', 'notes', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function spareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'warehouse_spare_part_stock')
            ->withPivot('current_stock', 'reorder_level')
            ->withTimestamps();
    }

    public function vehicleModels(): BelongsToMany
    {
        return $this->belongsToMany(VehicleModel::class, 'warehouse_vehicle_stock')
            ->withPivot('current_stock', 'reorder_level')
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Helpers ───────────────────────────────────
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::active()->first();
    }

    public static function generateCode(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->code, 4) + 1 : 1;
        return 'STK-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function getTotalStockValueAttribute(): float
    {
        $parts = \DB::table('warehouse_spare_part_stock as ws')
            ->join('spare_parts as sp', 'ws.spare_part_id', '=', 'sp.id')
            ->where('ws.warehouse_id', $this->id)
            ->sum(\DB::raw('ws.current_stock * sp.buying_price'));

        $vehicles = \DB::table('warehouse_vehicle_stock as wv')
            ->join('vehicle_models as vm', 'wv.vehicle_model_id', '=', 'vm.id')
            ->where('wv.warehouse_id', $this->id)
            ->sum(\DB::raw('wv.current_stock * vm.buying_price'));

        return (float)$parts + (float)$vehicles;
    }

    public function getLowStockCountAttribute(): int
    {
        $parts = \DB::table('warehouse_spare_part_stock')
            ->where('warehouse_id', $this->id)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->count();

        $vehicles = \DB::table('warehouse_vehicle_stock')
            ->where('warehouse_id', $this->id)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->count();

        return $parts + $vehicles;
    }
}
