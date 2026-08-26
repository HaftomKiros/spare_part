<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number', 'customer_id', 'user_id', 'warehouse_id',
        'sale_date', 'subtotal', 'discount', 'tax', 'total',
        'paid_amount', 'balance', 'payment_method', 'payment_status',
        'status', 'notes',
    ];

    protected $casts = [
        'sale_date'   => 'date',
        'subtotal'    => 'decimal:2',
        'discount'    => 'decimal:2',
        'tax'         => 'decimal:2',
        'total'       => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance'     => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->invoice_number, -4) + 1 : 1;
        return 'INV-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->customer?->name ?? 'Walk-in Customer';
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
            'completed' => 'success',
            'draft'     => 'secondary',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
