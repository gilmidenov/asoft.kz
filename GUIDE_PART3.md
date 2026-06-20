# Часть 6: API — маршруты и контроллеры {#часть-6}

## Что такое REST API

Наш Laravel работает как **API-сервер**: принимает HTTP-запросы от Vue.js и возвращает JSON. Это называется REST API.

```
Vue.js (браузер)          Laravel (сервер)
GET /api/products    →    ProductController@index  →  SELECT * FROM products
POST /api/cart       →    CartController@store     →  INSERT INTO cart_items
DELETE /api/cart/5   →    CartController@destroy   →  DELETE FROM cart_items WHERE id=5
```

| HTTP-метод | Действие | Пример |
|------------|----------|--------|
| GET | Получить данные | GET /api/products — список товаров |
| POST | Создать | POST /api/orders — создать заказ |
| PUT/PATCH | Обновить | PUT /api/cart/5 — обновить корзину |
| DELETE | Удалить | DELETE /api/cart/5 — убрать из корзины |

---

## 6.1 Установка Laravel Sanctum

**Sanctum** — пакет Laravel для аутентификации SPA (наш случай). Он выдаёт токены, которые Vue.js хранит в localStorage и отправляет с каждым запросом.

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Теперь в базе появится таблица `personal_access_tokens`.

В `app/Models/User.php` добавь трейт `HasApiTokens`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- добавить этот import

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; // <-- добавить HasApiTokens

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed', // Laravel автоматически хеширует пароль
    ];
}
```

---

## 6.2 Маршруты API

Файл `routes/api.php` — это карта всех API-эндпоинтов. Каждый маршрут говорит: "при таком HTTP-запросе вызови такой метод такого контроллера".

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;

// ============================================================
// ПУБЛИЧНЫЕ маршруты (без авторизации)
// ============================================================

// Каталог
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// Вендоры
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/{slug}', [VendorController::class, 'show']);

// Товары
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/products/search', [ProductController::class, 'search']);

// Корзина (гостевая — по session_id)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'store']);
Route::patch('/cart/{id}', [CartController::class, 'update']);
Route::delete('/cart/{id}', [CartController::class, 'destroy']);
Route::delete('/cart', [CartController::class, 'clear']);

// Аутентификация
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ============================================================
// ЗАЩИЩЁННЫЕ маршруты (только для авторизованных пользователей)
// middleware('auth:sanctum') — проверяет Bearer-токен в заголовке
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // Выход
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Профиль
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/me', [AuthController::class, 'update']);

    // Избранное
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{productId}', [FavoriteController::class, 'toggle']);

    // Заказы
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // ============================================================
    // ADMIN маршруты (только для роли admin)
    // ============================================================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('vendors', VendorController::class)->except(['index', 'show']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
```

**Объяснение `apiResource`:**

`Route::apiResource('products', ProductController::class)` — это сокращение, которое создаёт сразу 5 маршрутов:

| Метод | URL | Controller@method | Назначение |
|-------|-----|-------------------|-----------|
| GET | /api/admin/products | index | Список товаров |
| POST | /api/admin/products | store | Создать товар |
| GET | /api/admin/products/{id} | show | Один товар |
| PUT | /api/admin/products/{id} | update | Обновить |
| DELETE | /api/admin/products/{id} | destroy | Удалить |

---

## 6.3 Middleware для ролей

Создадим middleware `role`, чтобы ограничить доступ к admin-маршрутам:

```bash
php artisan make:middleware CheckRole
```

**app/Http/Middleware/CheckRole.php:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // handle() вызывается для каждого запроса
    // $request — объект HTTP-запроса
    // $next — следующий обработчик (передать запрос дальше)
    // $role — роль, которую мы проверяем (передаётся через middleware('role:admin'))
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Если пользователь не авторизован или его роль не совпадает
        if (!$request->user() || $request->user()->role !== $role) {
            // Возвращаем 403 Forbidden
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        // Всё ок — передаём запрос дальше
        return $next($request);
    }
}
```

Зарегистрируй middleware в `bootstrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Регистрируем наш middleware под именем 'role'
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 6.4 Контроллер AuthController

```bash
php artisan make:controller Api/AuthController
```

**app/Http/Controllers/Api/AuthController.php:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        // validate() — проверяет входящие данные по правилам.
        // Если данные не соответствуют — Laravel автоматически вернёт 422 с ошибками.
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            // email: обязательное | формат email | уникальное в таблице users
            'email'    => 'required|email|unique:users,email',
            // confirmed: должно быть поле password_confirmation с таким же значением
            'password' => 'required|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
        ]);

        // Создаём пользователя. Пароль хешируется автоматически через cast 'hashed'
        $user = User::create($data);

        // createToken() — создаёт токен Sanctum
        // 'auth-token' — произвольное имя токена
        // plainTextToken — строка токена, которую мы отдаём клиенту
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201); // 201 Created
    }

    /**
     * Вход в систему
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Ищем пользователя по email
        $user = User::where('email', $data['email'])->first();

        // Hash::check() сравнивает открытый пароль с хешем в БД
        if (!$user || !Hash::check($data['password'], $user->password)) {
            // 401 Unauthorized
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Выход (удалить токен)
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Удаляем только текущий токен (тот, что использован в этом запросе)
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Выход выполнен']);
    }

    /**
     * Текущий пользователь
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
```

---

## 6.5 Контроллер ProductController

```bash
php artisan make:controller Api/ProductController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Список товаров с фильтрами
     * GET /api/products?category=ofisnoye-po&vendor=microsoft&sort=price_asc&page=1
     */
    public function index(Request $request): JsonResponse
    {
        // Начинаем строить запрос
        // with() — "жадная загрузка" (eager loading):
        // загружает связанные данные одним дополнительным запросом,
        // а не отдельным запросом для каждого товара (избегаем N+1 проблему)
        $query = Product::with(['category', 'vendor', 'licenses'])
            ->active(); // применяем scope active()

        // Фильтр по категории
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                // whereHas — условие через связь (JOIN под капотом)
                $q->where('slug', $request->category);
            });
        }

        // Фильтр по вендору
        if ($request->filled('vendor')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('slug', $request->vendor);
            });
        }

        // Поиск по названию
        if ($request->filled('search')) {
            // ILIKE — регистронезависимый поиск в PostgreSQL
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Фильтр по цене
        if ($request->filled('price_from')) {
            $query->where('price_from', '>=', $request->price_from);
        }
        if ($request->filled('price_to')) {
            $query->where('price_from', '<=', $request->price_to);
        }

        // Теги
        if ($request->boolean('is_hit')) {
            $query->where('is_hit', true);
        }

        // Сортировка
        match ($request->get('sort', 'default')) {
            'price_asc'  => $query->orderBy('price_from', 'asc'),
            'price_desc' => $query->orderBy('price_from', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'new'        => $query->orderBy('created_at', 'desc'),
            default      => $query->orderBy('views_count', 'desc'),
        };

        // paginate(20) — возвращает 20 товаров на странице
        // Включает метаданные: total, current_page, last_page и т.д.
        $products = $query->paginate(20);

        return response()->json($products);
    }

    /**
     * Один товар
     * GET /api/products/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        // findOrFail — если не найдено, автоматически вернёт 404
        $product = Product::with(['category', 'vendor', 'licenses', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Увеличиваем счётчик просмотров
        // increment() — атомарный UPDATE counter = counter + 1
        $product->increment('views_count');

        return response()->json($product);
    }

    /**
     * Создать товар (admin)
     * POST /api/admin/products
     */
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
        ]);

        // Автоматически генерируем slug из названия
        $data['slug'] = Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    /**
     * Обновить товар (admin)
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'category_id'       => 'nullable|exists:categories,id',
            'vendor_id'         => 'nullable|exists:vendors,id',
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'status'            => 'in:active,inactive,out_of_stock',
        ]);

        $product->update($data);

        return response()->json($product);
    }

    /**
     * Удалить товар (admin)
     * DELETE /api/admin/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204); // 204 No Content
    }
}
```

---

## 6.6 Контроллер CartController

```bash
php artisan make:controller Api/CartController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Определяем идентификатор сессии гостя
    private function getSessionId(Request $request): string
    {
        // Если нет session_id в куки — генерируем
        return $request->cookie('cart_session', \Str::uuid());
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
```

---

## 6.7 Контроллер OrderController

```bash
php artisan make:controller Api/OrderController
```

```php
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
            'customer_name'  => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'comment'        => 'nullable|string',
        ]);

        // DB::transaction() — всё внутри выполняется как одна атомарная операция.
        // Если что-то пошло не так — все изменения откатываются.
        $order = DB::transaction(function () use ($request, $data) {
            // Получаем корзину пользователя
            $cartItems = CartItem::with(['product', 'license'])
                ->where('user_id', $request->user()->id)
                ->get();

            if ($cartItems->isEmpty()) {
                abort(422, 'Корзина пуста');
            }

            // Считаем суммы
            $subtotal = $cartItems->sum(fn($item) => $item->license->price * $item->quantity);

            // Генерируем уникальный номер заказа
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT);

            // Создаём заказ
            $order = Order::create([
                'order_number'   => $orderNumber,
                'user_id'        => $request->user()->id,
                'status'         => 'pending',
                'customer_name'  => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
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
}
```

---

## 6.8 Контроллеры CategoryController и VendorController

```bash
php artisan make:controller Api/CategoryController
php artisan make:controller Api/VendorController
```

**CategoryController:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        // Загружаем категории верхнего уровня с подкатегориями
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
}
```

---

# Часть 7: Аутентификация (Sanctum) {#часть-7}

## Как работает аутентификация

```
1. Пользователь вводит email + пароль
2. Vue.js: POST /api/auth/login  →  { email, password }
3. Laravel проверяет, создаёт токен
4. Laravel возвращает: { user: {...}, token: "1|abc123..." }
5. Vue.js сохраняет токен в localStorage
6. Для каждого следующего запроса Vue.js добавляет заголовок:
   Authorization: Bearer 1|abc123...
7. Laravel middleware 'auth:sanctum' проверяет токен и находит пользователя
```

## 7.1 Настройка CORS

Vue.js работает на `localhost:5173`, Laravel — на `localhost:8000`. Это разные порты = разные "источники". Браузер блокирует такие запросы по политике CORS.

В `config/cors.php` убедись:

```php
<?php

return [
    // Разрешённые пути (все /api/* маршруты)
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Разрешённые HTTP-методы
    'allowed_methods' => ['*'],

    // Разрешённые источники. В продакшене заменить на ['https://asoft.kz']
    'allowed_origins' => ['http://localhost:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Разрешить отправку кук (нужно для Sanctum SPA)
    'supports_credentials' => true,
];
```

---

# Часть 8: Frontend — Vue 3 + Vite {#часть-8}

## 8.1 Установка Vue 3

В Laravel 11 уже настроен Vite. Установим Vue:

```bash
npm install vue@3 @vitejs/plugin-vue
npm install vue-router@4 pinia axios
npm install -D tailwindcss @tailwindcss/forms postcss autoprefixer
npx tailwindcss init -p
```

**Что мы установили:**
- `vue@3` — фреймворк Vue.js версии 3
- `@vitejs/plugin-vue` — плагин Vite для обработки `.vue` файлов
- `vue-router@4` — маршрутизация (навигация между страницами)
- `pinia` — state management (глобальное состояние: корзина, пользователь)
- `axios` — HTTP-клиент для запросов к API
- `tailwindcss` — утилитарный CSS-фреймворк
- `@tailwindcss/forms` — плагин Tailwind для стилизации форм

---

## 8.2 Настройка Vite

**vite.config.js** — конфигурация Vite (сборщик frontend):

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        // Плагин Laravel: связывает Vite с Laravel
        // 'resources/js/app.js' — точка входа нашего приложения
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true, // Автоперезагрузка браузера при изменении Blade-файлов
        }),
        // Плагин Vue: позволяет Vite компилировать .vue файлы
        vue(),
    ],

    resolve: {
        alias: {
            // '@' — сокращение для 'resources/js/'
            // Вместо '../../../components/Button.vue' пишем '@/components/Button.vue'
            '@': '/resources/js',
        },
    },

    server: {
        // Настройки dev-сервера Vite
        port: 5173,
        proxy: {
            // Запросы к /api перенаправляем на Laravel
            '/api': 'http://localhost:8000',
        },
    },
})
```

---

## 8.3 Настройка Tailwind CSS

**tailwind.config.js:**

```javascript
/** @type {import('tailwindcss').Config} */
export default {
    // content — список файлов, которые Tailwind сканирует.
    // Он удаляет неиспользуемые CSS-классы (tree shaking).
    // Это делает итоговый CSS маленьким!
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            // Расширяем дефолтную палитру Tailwind нашими цветами Atlas Software
            colors: {
                primary: {
                    DEFAULT: '#059669', // emerald-600
                    50:  '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669', // основной
                    700: '#047857', // hover
                    800: '#065f46',
                    900: '#064e3b',
                },
                accent: {
                    DEFAULT: '#f97316', // orange-500
                    light: '#fed7aa',
                    dark: '#ea580c',
                },
                dark: '#1E293B',   // slate-800
                muted: '#64748B',  // slate-500
                header: '#0F172A', // slate-900
            },

            // Дополнительные шрифты
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'), // Стили для полей форм
    ],
}
```

**resources/css/app.css:**

```css
/* Директивы Tailwind — Vite заменяет их готовым CSS */
@tailwind base;       /* Базовые стили (сброс) */
@tailwind components; /* Компоненты (.btn, .card и т.д.) */
@tailwind utilities;  /* Утилиты (mt-4, text-lg и т.д.) */

/* Импорт шрифта Inter из Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* CSS-переменные для цветов (используем в кастомных стилях) */
:root {
    --color-primary: #059669;
    --color-accent: #f97316;
}

/* Базовые стили */
body {
    @apply font-sans text-dark bg-gray-50 antialiased;
}

/* Плавный скролл */
html {
    scroll-behavior: smooth;
}
```

---

## 8.4 Blade-шаблон — точка входа

`routes/web.php` — для Vue SPA нужен только один маршрут, который возвращает HTML-страницу с Vue:

```php
<?php

use Illuminate\Support\Facades\Route;

// Все маршруты, кроме /api/*, отдают одну HTML-страницу
// Vue Router сам разбирает URL и показывает нужный компонент
Route::get('/{any}', function () {
    return view('app'); // resources/views/app.blade.php
})->where('any', '.*'); // .*  — любой путь
```

**resources/views/app.blade.php:**

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Atlas Software — интернет-магазин лицензионного программного обеспечения в Казахстане">

    <title>Atlas Software — Лицензионное ПО</title>

    {{-- @vite — директива Blade, подключает CSS и JS через Vite --}}
    {{-- В разработке: подключает Vite dev server (горячая перезагрузка) --}}
    {{-- В продакшене: подключает собранные файлы из public/build/ --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- #app — контейнер, в который Vue монтирует своё приложение --}}
    <div id="app"></div>
</body>
</html>
```

---

## 8.5 Точка входа Vue

**resources/js/app.js:**

```javascript
// createApp — создаёт экземпляр Vue-приложения
import { createApp } from 'vue'
// Корневой компонент приложения
import App from './App.vue'
// Маршрутизатор
import router from './router'
// Хранилище состояния
import { createPinia } from 'pinia'

// Глобальная настройка axios
import axios from 'axios'
// Базовый URL для всех запросов
axios.defaults.baseURL = '/api'
// Разрешить отправку кук (нужно для CORS с credentials)
axios.defaults.withCredentials = true

// Интерцептор: перед каждым запросом добавляем токен авторизации
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token')
    if (token) {
        // Bearer-токен: стандартный способ передачи токена в API
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Интерцептор ответов: обрабатываем 401 (токен устарел)
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Если токен невалиден — разлогиниваем
            localStorage.removeItem('auth_token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Делаем axios доступным глобально через app.config.globalProperties
// Это позволяет использовать this.$axios в Options API
// (мы используем Composition API, поэтому импортируем напрямую)
const app = createApp(App)

app.use(createPinia()) // Подключаем Pinia
app.use(router)        // Подключаем Vue Router

// mount('#app') — монтирует Vue в элемент <div id="app">
app.mount('#app')
```

---
