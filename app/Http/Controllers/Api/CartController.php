<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function getSessionId(Request $request): string
    {
        // Если нет session_id в куки — генерируем
        return $request->cookie('cart_session', Str::uuid());
    }

    /**
     * Получить корзину
     * GET /api/cart
     */
    public function index(Request $request): JsonResponse
    {
        $query = CartItem::with(['product', 'license']);

        if ($request->user()) {
            // Авторизованный пользователь
            $query->where('user_id', $request->user()->id);
        } else {
            // Гость — по session_id
            $query->where('session_id', $this->getSessionId($request));
        }

        $items = $query->get();

        // Считаем итоговую сумму
        $total = $items->sum(function ($item) {
            return $item->license->price * $item->quantity;
        });

        return response()->json([
            'items' => $items,
            'total' => $total,
            'count' => $items->sum('quantity'),
        ]);
    }

    /**
     * Добавить товар в корзину
     * POST /api/cart
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_license_id' => 'required|exists:product_licenses,id',
            'quantity'           => 'integer|min:1|max:100',
        ]);

        $license = ProductLicense::findOrFail($data['product_license_id']);

        // updateOrCreate — обновить если существует, создать если нет
        $item = CartItem::updateOrCreate(
        // Условие поиска
            [
                'user_id'            => $request->user()?->id,
                'session_id'         => $request->user() ? null : $this->getSessionId($request),
                'product_license_id' => $data['product_license_id'],
            ],
            // Данные для создания/обновления
            [
                'product_id' => $license->product_id,
                'quantity'   => $data['quantity'] ?? 1,
            ]
        );

        return response()->json($item->load(['product', 'license']), 201);
    }

    /**
     * Обновить количество
     * PATCH /api/cart/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = CartItem::findOrFail($id);
        $item->update(['quantity' => $request->validate(['quantity' => 'required|integer|min:1'])['quantity']]);
        return response()->json($item);
    }

    /**
     * Удалить позицию
     * DELETE /api/cart/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        CartItem::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Очистить корзину
     * DELETE /api/cart
     */
    public function clear(Request $request): JsonResponse
    {
        if ($request->user()) {
            CartItem::where('user_id', $request->user()->id)->delete();
        } else {
            CartItem::where('session_id', $this->getSessionId($request))->delete();
        }
        return response()->json(null, 204);
    }
}
