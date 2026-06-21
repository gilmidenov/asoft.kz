<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Создать заказ из корзины
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // DB::transaction() — всё внутри выполняется как одна атомарная операция.
        // Если что-то пошло не так — все изменения откатываются.
        $order = DB::transaction(function () use ($request, $data, $user) {
            // Получаем корзину пользователя
            $cartItems = CartItem::with(['product', 'license'])
                ->where('user_id', $user->id)
                ->get();

            if ($cartItems->isEmpty()) {
                abort(422, 'Корзина пуста');
            }

            // Считаем суммы
            $subtotal = $cartItems->sum(fn($item) => $item->license->price * $item->quantity);

            // Генерируем уникальный номер заказа
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT);

            // Создаём заказ — данные покупателя берём из профиля пользователя
            $order = Order::create([
                'order_number'   => $orderNumber,
                'user_id'        => $user->id,
                'status'         => 'pending',
                'customer_name'  => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? null,
                'comment'        => $data['comment'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => 0,
                'total'          => $subtotal,
            ]);

            // Создаём позиции заказа (копируем данные!)
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,
                    'license_name' => $item->license->name,
                    'price'        => $item->license->price,
                    'quantity'     => $item->quantity,
                ]);
            }

            // Очищаем корзину
            CartItem::where('user_id', $request->user()->id)->delete();

            return $order;
        });

        return response()->json($order->load('items'), 201);
    }

    /**
     * Список заказов пользователя
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Один заказ
     * GET /api/orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Обновить статус заказа (только для admin)
     * PATCH /api/admin/orders/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,processing,completed,cancelled,refunded',
        ]);

        $order = Order::with('items')->findOrFail($id);
        $order->update(['status' => $data['status']]);

        return response()->json($order->fresh('items'));
    }

    /**
     * Список всех заказов (только для admin)
     * GET /api/admin/orders
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $orders = Order::with(['items', 'user'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }
}
