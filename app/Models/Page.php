<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PageItem::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(PageItem::class)->orderBy('sort_order');
    }
}
