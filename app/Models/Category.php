<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    // $fillable — список полей, которые можно заполнять через массив
    // Это защита от "mass assignment" атаки — нельзя случайно перезаписать id или другие поля
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    // $casts — автоматическое приведение типов при чтении из БД
    // PostgreSQL хранит boolean как 't'/'f', cast преобразует в true/false PHP
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ===== СВЯЗИ (Relations) =====

    // Дочерние категории (подкатегории)
    // hasMany — "эта категория имеет много подкатегорий"
    // 'parent_id' — по какому полю связь
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Родительская категория
    // belongsTo — "эта категория принадлежит одной родительской"
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Товары этой категории
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
                ? (str_starts_with($value, 'http') ? $value : Storage::disk('public')->url($value))
                : null,
        );
    }
}
