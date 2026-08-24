<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'customer_type',
        'balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
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

    public function getTotalSalesAttribute(): float
    {
        return $this->sales()->sum('total');
    }

    public function getTotalSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }

    public static function generateCode(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->customer_code, 5) + 1 : 1;
        return 'CUST-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
