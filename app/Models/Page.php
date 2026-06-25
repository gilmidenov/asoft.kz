<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'body',
        'cover_image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected function coverImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && !str_starts_with($value, 'http')
                ? Storage::disk('public')->url($value)
                : $value
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(PageItem::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(PageItem::class)->orderBy('sort_order');
    }
}
