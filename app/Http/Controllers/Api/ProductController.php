<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'vendor', 'licenses'])
            ->active();

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('vendor')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('slug', $request->vendor);
            });
        }

        if ($request->filled('search')) {
            $op      = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $pattern = '%' . $request->search . '%';
            $query->where(function ($q) use ($op, $pattern) {
                $q->where('name', $op, $pattern)
                  ->orWhere('short_description', $op, $pattern);
            });
        }

        if ($request->filled('price_from')) {
            $query->where('price_from', '>=', $request->price_from);
        }
        if ($request->filled('price_to')) {
            $query->where('price_from', '<=', $request->price_to);
        }

        if ($request->boolean('is_hit')) {
            $query->where('is_hit', true);
        }

        match ($request->input('sort', 'default')) {
            'price_asc'  => $query->orderBy('price_from', 'asc'),
            'price_desc' => $query->orderBy('price_from', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'new'        => $query->orderBy('created_at', 'desc'),
            default      => $query->orderBy('views_count', 'desc'),
        };

        $products = $query->paginate(20);

        return response()->json($products);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'vendor'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $op      = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $pattern = '%' . $request->search . '%';
            $query->where('name', $op, $pattern);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate($request->input('per_page', 20));

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['category', 'vendor', 'licenses', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();
        $product->increment('views_count');

        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'category_id'       => 'nullable|exists:categories,id',
            'vendor_id'         => 'nullable|exists:vendors,id',
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'version'           => 'nullable|string|max:50',
            'language'          => 'nullable|string|max:100',
            'delivery_type'     => 'in:download,box,key',
            'status'            => 'in:active,inactive,out_of_stock',
            'is_hit'            => 'boolean',
            'is_new'            => 'boolean',
            'is_sale'           => 'boolean',
            'price_from'        => 'nullable|numeric|min:0',
            'stock_quantity'    => 'nullable|integer|min:0',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'category_id'       => 'nullable|exists:categories,id',
            'vendor_id'         => 'nullable|exists:vendors,id',
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'version'           => 'nullable|string|max:50',
            'language'          => 'nullable|string|max:100',
            'delivery_type'     => 'sometimes|in:download,box,key',
            'status'            => 'sometimes|in:active,inactive,out_of_stock',
            'is_hit'            => 'sometimes|boolean',
            'is_new'            => 'sometimes|boolean',
            'is_sale'           => 'sometimes|boolean',
            'price_from'        => 'nullable|numeric|min:0',
            'stock_quantity'    => 'nullable|integer|min:0',
        ]);

        $product->update($data);
        $product->refresh();

        return response()->json($product);
    }

    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
