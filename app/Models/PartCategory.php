<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'status',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PartCategory::class, 'parent_id');
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(SparePart::class, 'part_category_id');
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function getSparePartsCountAttribute(): int
    {
        return $this->spareParts()->count();
    }
}
