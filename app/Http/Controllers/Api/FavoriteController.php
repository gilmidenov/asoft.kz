<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::with(['product.licenses', 'product.vendor'])
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($favorites);
    }

    public function toggle(Request $request, int $productId): JsonResponse
    {
        Product::findOrFail($productId);

        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favorited' => false]);
        }

        Favorite::create([
            'user_id'    => $request->user()->id,
            'product_id' => $productId,
        ]);

        return response()->json(['favorited' => true]);
    }
}
