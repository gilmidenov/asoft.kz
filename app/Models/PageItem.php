<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PageItem extends Model
{
    protected $fillable = [
        'page_id',
        'title',
        'content',
        'file_path',
        'file_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // Возвращает полный URL файла (изображение или PDF)
    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
                ? (str_starts_with($value, 'http') ? $value : Storage::disk('public')->url($value))
                : null,
        );
    }
}
