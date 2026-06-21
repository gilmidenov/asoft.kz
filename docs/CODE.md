# Документация по коду

## Архитектура проекта

**Backend**: Laravel 11 (PHP 8.4) — REST API  
**Frontend**: Vue 3 + Pinia + Vue Router + Tailwind CSS  
**База данных**: PostgreSQL 16  
**Аутентификация**: Laravel Sanctum (Bearer-токены)  
**Инфраструктура**: Docker (nginx, php-fpm, postgres, node)

---

## Backend (Laravel API)

### Структура контроллеров

Все API-контроллеры находятся в `app/Http/Controllers/Api/`.

#### `AuthController`

| Метод | Route | Описание |
|-------|-------|----------|
| `register` | `POST /api/auth/register` | Регистрация. Создаёт юзера, возвращает `{user, token}` |
| `login` | `POST /api/auth/login` | Логин по email+password, возвращает `{user, token}` |
| `logout` | `POST /api/auth/logout` | Удаляет текущий access token. Использует `?->delete()` на случай null |
| `me` | `GET /api/auth/me` | Данные текущего пользователя |
| `update` | `PATCH /api/auth/me` | Обновление профиля (name, phone) |

**Токены**: Sanctum генерирует plain-text токены через `createToken('auth-token')->plainTextToken`. Токен хранится в localStorage на клиенте.

---

#### `ProductController`

| Метод | Route | Описание |
|-------|-------|----------|
| `index` | `GET /api/products` | Список товаров с фильтрами и пагинацией |
| `show` | `GET /api/products/{slug}` | Карточка товара. Увеличивает `views_count` |
| `store` | `POST /api/admin/products` | Создание товара (admin) |
| `update` | `PUT /api/admin/products/{id}` | Обновление товара (admin) |
| `destroy` | `DELETE /api/admin/products/{id}` | Удаление товара (admin) |

**Параметры фильтрации** (`GET /api/products`):

| Параметр | Тип | Описание |
|----------|-----|----------|
| `search` | string | Поиск по name и short_description (ILIKE в PostgreSQL, LIKE в SQLite) |
| `category` | string | Slug категории |
| `vendor` | string | Slug вендора |
| `price_from` | number | Минимальная цена |
| `price_to` | number | Максимальная цена |
| `is_hit` | 0/1 | Только хиты |
| `sort` | string | `default`, `price_asc`, `price_desc`, `name_asc`, `new` |
| `per_page` | number | Размер страницы (по умолчанию 20) |
| `page` | number | Номер страницы |

**Заметка по поиску**: Контроллер определяет драйвер БД через `DB::connection()->getDriverName()` и использует `ILIKE` для PostgreSQL (регистронезависимый Unicode) или `LIKE` для SQLite (тесты).

---

#### `CategoryController`

| Метод | Route | Описание |
|-------|-------|----------|
| `index` | `GET /api/categories` | Только активные корневые категории с подкатегориями |
| `show` | `GET /api/categories/{slug}` | Категория с детьми и родителем |
| `store` | `POST /api/admin/categories` | Создание. Slug генерируется из `Str::slug(name)` |
| `update` | `PUT /api/admin/categories/{id}` | Обновление. Slug пересчитывается при смене name |
| `destroy` | `DELETE /api/admin/categories/{id}` | Удаление (связанные товары получают `category_id = null`) |

---

#### `OrderController`

| Метод | Route | Описание |
|-------|-------|----------|
| `store` | `POST /api/orders` | Создаёт заказ из корзины (атомарная транзакция) |
| `index` | `GET /api/orders` | Заказы текущего пользователя |
| `show` | `GET /api/orders/{id}` | Один заказ (только свой) |
| `adminIndex` | `GET /api/admin/orders` | Все заказы для admin (фильтр по `?status=`) |
| `updateStatus` | `PATCH /api/admin/orders/{id}/status` | Смена статуса (admin) |

**Создание заказа** (`store`): выполняется в `DB::transaction()`. Копирует `product_name` и `license_name` из товара на момент покупки — чтобы изменение цен позже не ломало историю заказов.

**Статусы заказа**: `pending` → `paid` → `processing` → `completed` | `cancelled` | `refunded`

---

#### `CartController`

Корзина работает для авторизованных (по `user_id`) и гостей (по `session_id` из заголовка `X-Session-Id`).

| Метод | Route | Описание |
|-------|-------|----------|
| `index` | `GET /api/cart` | Список позиций |
| `store` | `POST /api/cart` | Добавить/обновить позицию |
| `update` | `PATCH /api/cart/{id}` | Изменить количество |
| `destroy` | `DELETE /api/cart/{id}` | Удалить позицию |
| `clear` | `DELETE /api/cart` | Очистить корзину |

---

#### `VendorController` / `FavoriteController`

Стандартные CRUD. `FavoriteController::toggle()` добавляет или удаляет товар из избранного (upsert-логика через firstOrCreate + delete).

---

### Middleware

#### `CheckRole` (`app/Http/Middleware/CheckRole.php`)

Проверяет роль пользователя. Используется в маршрутах `role:admin`.

```php
// Регистрация в bootstrap/app.php:
->withMiddleware(function (Middleware $m) {
    $m->alias(['role' => CheckRole::class]);
})
```

Возвращает HTTP 403 если `$user->role !== $requiredRole`.

---

### Модели

| Модель | Таблица | Ключевые поля |
|--------|---------|---------------|
| `User` | `users` | `name`, `email`, `password`, `phone`, `role` (`customer`/`admin`) |
| `Product` | `products` | `name`, `slug`, `status`, `is_hit`, `is_new`, `is_sale`, `price_from`, `views_count` |
| `Category` | `categories` | `name`, `slug`, `parent_id`, `is_active`, `sort_order` |
| `Vendor` | `vendors` | `name`, `slug`, `is_active` |
| `ProductLicense` | `product_licenses` | `product_id`, `name`, `price`, `type`, `devices`, `duration_months` |
| `CartItem` | `cart_items` | `user_id`, `session_id`, `product_id`, `product_license_id`, `quantity` |
| `Order` | `orders` | `order_number`, `user_id`, `status`, `total`, `customer_*` |
| `OrderItem` | `order_items` | `order_id`, `product_id`, `product_name`, `license_name`, `price`, `quantity` |
| `Favorite` | `favorites` | `user_id`, `product_id` |

**Важно**: `OrderItem` хранит копии `product_name` и `license_name` на момент покупки — это намеренно, чтобы изменение товара в будущем не меняло историю заказов.

---

## Frontend (Vue 3)

### Структура файлов

```
resources/js/
├── App.vue              # Корневой компонент (загружает user + cart)
├── main.js              # Инициализация Vue + Pinia + Router + Axios
├── router/
│   └── index.js         # Маршруты, навигационный guard
├── stores/
│   ├── auth.js          # Pinia-стор: user, token, login/logout/register
│   ├── cart.js          # Pinia-стор: корзина, count
│   └── catalog.js       # Pinia-стор: категории + вендоры (кэш)
├── pages/
│   ├── HomePage.vue     # Главная: hero, категории, хиты, новинки
│   ├── CatalogPage.vue  # Каталог с фильтрами и пагинацией
│   ├── ProductPage.vue  # Страница товара
│   ├── CartPage.vue     # Корзина
│   ├── CheckoutPage.vue # Оформление заказа
│   ├── LoginPage.vue    # Вход
│   ├── RegisterPage.vue # Регистрация
│   ├── AccountPage.vue  # Личный кабинет + история заказов
│   ├── FavoritesPage.vue# Избранное
│   ├── VendorsPage.vue  # Список вендоров
│   ├── NotFoundPage.vue # 404
│   └── admin/           # Административная панель
│       ├── AdminLayout.vue    # Layout с сайдбаром
│       ├── DashboardPage.vue  # Дашборд со статистикой
│       ├── ProductsPage.vue   # CRUD товаров
│       ├── CategoriesPage.vue # CRUD категорий
│       ├── VendorsPage.vue    # CRUD вендоров
│       └── OrdersPage.vue     # Управление заказами
└── components/
    ├── layout/
    │   ├── AppHeader.vue   # Хедер: логотип, поиск, корзина, меню
    │   └── AppFooter.vue   # Футер
    ├── catalog/
    │   └── ProductCard.vue # Карточка товара в сетке
    └── ui/
        └── BasePagination.vue # Компонент пагинации
```

---

### Роутер (`router/index.js`)

**Публичные маршруты**: `/`, `/catalog`, `/catalog/:slug`, `/product/:slug`, `/cart`, `/vendors`

**`guest` маршруты** (перенаправляют в `/account` если авторизован): `/login`, `/register`

**`requiresAuth` маршруты** (перенаправляют в `/login` если нет токена): `/checkout`, `/account`, `/favorites`

**`requiresAdmin`** маршруты: `/admin/*` (защита только через middleware на backend; frontend показывает страницу, но все запросы вернут 403 без admin-роли)

---

### Stores (Pinia)

#### `auth.js`

```js
// Ключевые свойства:
token          // ref, из localStorage
user           // ref, объект пользователя
isAuthenticated // computed, !!token
isAdmin        // computed, user?.role === 'admin'

// Методы:
login(email, password)
register(name, email, password, passwordConfirmation)
logout()
fetchUser()   // вызывается при старте App.vue
```

#### `cart.js`

Хранит позиции корзины и предоставляет `count` (computed). Корзина синхронизируется с backend при монтировании `App.vue`.

#### `catalog.js`

Кэширует список категорий и вендоров. `fetchCategories()` делает запрос только если список ещё не загружен.

---

### Axios настройка (`main.js`)

```js
axios.defaults.baseURL = '/api'

// Интерсептор: добавляет Bearer-токен к каждому запросу
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})
```

---

## Тесты

Тесты находятся в `tests/Feature/`. Запуск:

```bash
docker exec asoft_app php artisan test
# или отдельный файл:
docker exec asoft_app php artisan test tests/Feature/AuthTest.php --testdox
```

| Файл | Покрытие |
|------|----------|
| `AuthTest.php` | Регистрация, логин, logout, профиль |
| `ProductTest.php` | Список, поиск, фильтры, CRUD admin |
| `CategoryTest.php` | Список, CRUD admin, иерархия |
| `OrderTest.php` | Создание заказа, расчёт суммы, смена статуса admin |

**Конфигурация тестов** (`phpunit.xml`): используется SQLite в памяти (`DB_DATABASE=:memory:`). Поиск в тестах работает через `LIKE` (SQLite), в продакшне — через `ILIKE` (PostgreSQL). Переключение автоматическое.

---

## Известные ограничения и точки роста

- Загрузка изображений товаров: поле `main_image` и таблица `product_images` есть в БД, но интерфейс загрузки файлов не реализован (только URL).
- Лицензионные ключи (`order_items.license_key`) хранятся в БД, но механизм их генерации/выдачи после оплаты не реализован.
- Email-уведомления не настроены.
- Платёжная интеграция отсутствует (статусы меняются вручную через admin-панель).
