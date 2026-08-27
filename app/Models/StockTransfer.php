<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'user_id',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The stub Purchase records created in the destination warehouse. */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** All destination purchase_items created for this transfer (via stub purchases). */
    public function destinationItems()
    {
        return PurchaseItem::whereHas('purchase', fn($q) => $q->where('stock_transfer_id', $this->id));
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->transfer_number, -4) + 1 : 1;
        return 'TRF-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
