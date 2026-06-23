<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::with(['children', 'parent'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($category);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image'       => 'nullable|string|max:500',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $category = Category::create($data);

        return response()->json($category->load('children'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image'       => 'nullable|string|max:500',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json($category->fresh('children'));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
        ]);

        if ($category->getRawOriginal('image') && !str_starts_with($category->getRawOriginal('image'), 'http')) {
            Storage::disk('public')->delete($category->getRawOriginal('image'));
        }

        $path = $request->file('image')->store('categories', 'public');
        $category->update(['image' => $path]);

        return response()->json($category->fresh('children'));
    }
}
