<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'access_level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse')->withTimestamps();
    }

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

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Super Admin — role has 'all' permission.
     * Sees ALL warehouses, ALL transactions. No filters applied.
     */
    public function isAdmin(): bool
    {
        return $this->hasPermission('all');
    }

    /**
     * Manager — role has 'warehouse.full_access' OR 'all' permission.
     * Sees ALL transactions in assigned warehouses (no user_id filter).
     */
    public function isManager(): bool
    {
        return $this->hasPermission('warehouse.full_access') || $this->hasPermission('all');
    }

    /**
     * Returns true when the user_id filter should NOT be applied.
     * Managers and Super Admins bypass the user_id restriction.
     */
    public function seesAllUsers(): bool
    {
        return $this->isManager();
    }

    /**
     * Returns the warehouse IDs this user can access.
     * Super Admin (has 'all' permission) sees ALL warehouses.
     * Others see only their assigned warehouses.
     */
    public function accessibleWarehouseIds(): array
    {
        if ($this->isAdmin()) {
            return \App\Models\Warehouse::active()->pluck('id')->toArray();
        }

        $ids = $this->warehouses()->pluck('warehouses.id')->toArray();

        if (empty($ids)) {
            return \App\Models\Warehouse::active()->pluck('id')->toArray();
        }

        return $ids;
    }

    /**
     * Returns an Eloquent query scoped to the user's accessible warehouses.
     */
    public function accessibleWarehouses()
    {
        if ($this->isAdmin()) {
            return \App\Models\Warehouse::active();
        }

        $ids = $this->warehouses()->pluck('warehouses.id')->toArray();

        if (empty($ids)) {
            return \App\Models\Warehouse::active();
        }

        return \App\Models\Warehouse::active()->whereIn('id', $ids);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : asset('images/default-avatar.png');
    }
}
