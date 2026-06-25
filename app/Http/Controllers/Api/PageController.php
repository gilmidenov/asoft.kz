<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    // Публичный список активных разделов (для навигации)
    public function index(): JsonResponse
    {
        return response()->json(
            Page::where('is_active', true)->orderBy('sort_order')->get()
        );
    }

    // Публичная страница раздела вместе с элементами
    public function show(string $slug): JsonResponse
    {
        $page = Page::with('items')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json($page);
    }

    // ── Админ: управление разделами ────────────────────────

    public function adminIndex(): JsonResponse
    {
        return response()->json(
            Page::withCount('allItems')->orderBy('sort_order')->get()
        );
    }

    public function storePage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:30',
            'description' => 'nullable|string',
            'type'        => 'in:catalog,section',
            'body'        => 'nullable|string',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);

        return response()->json(Page::create($data), 201);
    }

    public function updatePage(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:30',
            'description' => 'nullable|string',
            'type'        => 'in:catalog,section',
            'body'        => 'nullable|string',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $page->update($data);

        return response()->json($page->fresh());
    }

    public function deleteCover(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $raw = $page->getRawOriginal('cover_image');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $page->update(['cover_image' => null]);

        return response()->json($page->fresh());
    }

    public function deleteItemFile(int $id): JsonResponse
    {
        $item = PageItem::findOrFail($id);

        $raw = $item->getRawOriginal('file_path');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $item->update(['file_path' => null, 'file_type' => 'text']);

        return response()->json($item->fresh());
    }

    public function uploadCover(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $raw = $page->getRawOriginal('cover_image');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $path = $request->file('image')->store('page-covers', 'public');
        $page->update(['cover_image' => $path]);

        return response()->json($page->fresh());
    }

    public function destroyPage(int $id): JsonResponse
    {
        Page::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    // ── Админ: управление элементами раздела ──────────────

    // GET /admin/pages/{id}/items — все элементы раздела
    public function adminItems(int $pageId): JsonResponse
    {
        $page = Page::findOrFail($pageId);

        return response()->json($page->allItems()->get());
    }

    // POST /admin/pages/{id}/items — создать элемент
    public function storeItem(Request $request, int $pageId): JsonResponse
    {
        Page::findOrFail($pageId);

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'body'       => 'nullable|string',
            'file_type'  => 'in:image,pdf,text',
            'sort_order' => 'integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['page_id'] = $pageId;
        $data['file_type'] = $data['file_type'] ?? 'text';

        return response()->json(PageItem::create($data), 201);
    }

    // PUT /admin/items/{id} — обновить элемент
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $item = PageItem::findOrFail($id);

        $data = $request->validate([
            'title'      => 'sometimes|string|max:255',
            'content'    => 'nullable|string',
            'body'       => 'nullable|string',
            'file_type'  => 'in:image,pdf,text',
            'sort_order' => 'integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $item->update($data);

        return response()->json($item->fresh());
    }

    // DELETE /admin/items/{id}
    public function destroyItem(int $id): JsonResponse
    {
        $item = PageItem::findOrFail($id);

        $raw = $item->getRawOriginal('file_path');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $item->delete();

        return response()->json(null, 204);
    }

    // POST /admin/items/{id}/file — загрузить файл (изображение или PDF)
    public function uploadFile(Request $request, int $id): JsonResponse
    {
        $item = PageItem::findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,gif,pdf|max:20480',
        ]);

        $raw = $item->getRawOriginal('file_path');
        if ($raw && !str_starts_with($raw, 'http')) {
            Storage::disk('public')->delete($raw);
        }

        $uploadedFile = $request->file('file');
        $mime         = $uploadedFile->getMimeType();
        $fileType     = str_contains($mime, 'pdf') ? 'pdf' : 'image';
        $folder       = $fileType === 'pdf' ? 'page-docs' : 'page-images';

        $path = $uploadedFile->store($folder, 'public');
        $item->update(['file_path' => $path, 'file_type' => $fileType]);

        return response()->json($item->fresh());
    }
}
