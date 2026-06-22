<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_description', 'description',
        'category_id', 'vendor_id', 'version', 'language',
        'delivery_type', 'main_image', 'price_from', 'status',
        'is_hit', 'is_new', 'is_sale',
        'meta_title', 'meta_description', 'meta_keywords', 'views_count',
        'stock_quantity',
    ];

    protected $casts = [
        'price_from'  => 'decimal:2',
        'is_hit'      => 'boolean',
        'is_new'      => 'boolean',
        'is_sale'     => 'boolean',
        'views_count'    => 'integer',
        'stock_quantity' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ProductLicense::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHit($query)
    {
        return $query->where('is_hit', true);
    }
}
