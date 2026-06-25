<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    // Публичный список активных баннеров (для главной страницы)
    public function index(): JsonResponse
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($banners);
    }

    // ── Админ CRUD ──────────────────────────────────────────

    public function adminIndex(): JsonResponse
    {
        return response()->json(Banner::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url'  => 'nullable|string|max:500',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        return response()->json(Banner::create($data), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'subtitle'    => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url'  => 'nullable|string|max:500',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $banner->update($data);

        return response()->json($banner->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        // Удаляем файл изображения если он хранится локально
        $raw = $banner->getRawOriginal('image');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $banner->delete();

        return response()->json(null, 204);
    }

    // POST /admin/banners/{id}/image — загрузка/замена изображения
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:8192',
        ]);

        // Удаляем старое изображение
        $raw = $banner->getRawOriginal('image');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $path = $request->file('image')->store('banners', 'public');
        $banner->update(['image' => $path]);

        return response()->json($banner->fresh());
    }
}
