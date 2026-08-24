<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_code',
        'name',
        'company',
        'phone',
        'email',
        'address',
        'city',
        'contact_person',
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

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
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

    public function getTotalPurchasesAttribute(): float
    {
        return $this->purchases()->sum('total');
    }

    public function getTotalPurchasesCountAttribute(): int
    {
        return $this->purchases()->count();
    }

    /**
     * Generate next supplier code: SUP-001, SUP-002 ...
     */
    public static function generateCode(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->supplier_code, 4) + 1 : 1;
        return 'SUP-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
