<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'user_id',
        'purchase_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'balance',
        'payment_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date'      => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'balance'       => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->purchase_number, -4) + 1 : 1;
        return 'PO-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        return match($this->payment_status) {
            'paid'    => 'success',
            'partial' => 'warning',
            default   => 'danger',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'received'  => 'success',
            'ordered'   => 'primary',
            'draft'     => 'secondary',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
