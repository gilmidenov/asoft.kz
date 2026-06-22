# Документация по коду

## Архитектура проекта

**Backend**: Laravel 13 (PHP 8.4-fpm) — REST API  
**Frontend**: Vue 3 + Pinia + Vue Router + Tailwind CSS  
**База данных**: PostgreSQL 16  
**Аутентификация**: Laravel Sanctum (Bearer-токены)  
**Инфраструктура**: Docker (nginx:alpine, php-fpm, postgres:16-alpine)

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
| `index` | `GET /api/products` | Публичный список товаров (только `status=active`) с фильтрами и пагинацией |
| `adminIndex` | `GET /api/admin/products` | Список всех товаров для admin (любой статус) |
| `show` | `GET /api/products/{slug}` | Карточка товара. Увеличивает `views_count` |
| `store` | `POST /api/admin/products` | Создание товара (admin) |
| `update` | `PUT /api/admin/products/{id}` | Обновление товара (admin) |
| `destroy` | `DELETE /api/admin/products/{id}` | Удаление товара + файл изображения (admin) |
| `syncLicenses` | `POST /api/admin/products/{id}/licenses/sync` | Синхронизация лицензий (admin) |
| `uploadImage` | `POST /api/admin/products/{id}/image` | Загрузка главного изображения (admin) |
| `deleteImage` | `DELETE /api/admin/products/{id}/image` | Удаление главного изображения (admin) |

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

**Синхронизация лицензий** (`syncLicenses`): удаляет все старые лицензии товара и создаёт новые из переданного массива. После синхронизации автоматически пересчитывает `price_from` как минимальную цену среди лицензий. Это поле используется каталогом для отображения «от X ₸».

**Изображения**: хранятся в `storage/app/public/products/`. При загрузке старый файл удаляется. `main_image` в БД хранится как относительный путь (`products/filename.jpg`), но Eloquent accessor на модели `Product` преобразует его в полный URL (`https://asoft.kz/storage/products/filename.jpg`) — все API-ответы возвращают уже готовый URL.

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
| `Product` | `products` | `name`, `slug`, `status`, `is_hit`, `is_new`, `is_sale`, `price_from`, `stock_quantity`, `main_image`, `views_count` |
| `Category` | `categories` | `name`, `slug`, `parent_id`, `is_active`, `sort_order` |
| `Vendor` | `vendors` | `name`, `slug`, `is_active` |
| `ProductLicense` | `product_licenses` | `product_id`, `name`, `price`, `old_price`, `type`, `devices`, `duration_months`, `in_stock`, `sort_order` |
| `ProductImage` | `product_images` | `product_id`, `path`, `alt`, `sort_order` |
| `CartItem` | `cart_items` | `user_id`, `session_id`, `product_id`, `product_license_id`, `quantity` |
| `Order` | `orders` | `order_number`, `user_id`, `status`, `total`, `customer_*` |
| `OrderItem` | `order_items` | `order_id`, `product_id`, `product_name`, `license_name`, `price`, `quantity` |
| `Favorite` | `favorites` | `user_id`, `product_id` |

**Важно**: `OrderItem` хранит копии `product_name` и `license_name` на момент покупки — это намеренно, чтобы изменение товара в будущем не меняло историю заказов.

#### Accessor `mainImage` в модели `Product`

```php
protected function mainImage(): Attribute
{
    return Attribute::make(
        get: fn($value) => $value ? Storage::disk('public')->url($value) : null,
    );
}
```

Преобразует относительный путь из БД в полный URL (`https://asoft.kz/storage/products/...`). Благодаря этому все клиенты API получают готовый URL без дополнительной обработки.

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
│       ├── ProductsPage.vue   # CRUD товаров с лицензиями и изображениями
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

## Работа с товарами в админ-панели

### Создание товара

1. Откройте `/admin/products` → нажмите **«+ Добавить товар»**.
2. Заполните обязательное поле **Название** и опциональные поля: категория, вендор, описание, версия, язык, тип доставки.
3. Установите **Количество на складе** (пусто = неограничено).
4. Установите **Статус**: Активен / Неактивен / Нет в наличии.
5. Добавьте **лицензии** через кнопку «+ Добавить» в секции «Варианты лицензий»:
   - Название (напр. «1 ПК бессрочно»)
   - Цена (₸) — обязательно
   - Старая цена — для отображения зачёркнутой цены
   - Тип: Бессрочная / Подписка / Корпоративная
   - Срок (мес.) — для подписок
   - Устройства — «1», «3», «unlimited»
   - Флаг «В наличии»
6. Загрузите **изображение** через зону загрузки (JPG/PNG/WebP, до 4 МБ).
7. Нажмите **«Сохранить»** — товар создаётся, лицензии синхронизируются, изображение загружается.

После сохранения поле `price_from` автоматически становится равным минимальной цене среди лицензий. Именно это значение используется в каталоге («от X ₸»).

### Редактирование товара

Нажмите **«Изменить»** в строке таблицы. Форма откроется с текущими данными и всеми лицензиями. Логика сохранения аналогична созданию.

### Удаление товара

Нажмите **«Удалить»** — удаляются товар, все его лицензии и файл изображения.

### Управление количеством

Поле **«Количество на складе»** отражает физический остаток (для лицензионных ключей / коробочных версий). Пустое значение означает «неограничено». Текущее количество отображается в колонке **Кол-во** таблицы.

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

## Известные исправления и решения

### OPcache: изменения PHP-файлов не применялись

**Проблема**: в `docker/php/prod.ini` было `opcache.validate_timestamps = 0`. PHP никогда не перечитывал файлы с диска — все изменения в контроллерах молча игнорировались до перезапуска контейнера.

**Решение**: изменено на `opcache.validate_timestamps = 1`. PHP теперь проверяет дату изменения файла и сбрасывает кэш при необходимости.

### Изображения товаров возвращали 404

**Проблема**: `main_image` хранился как `products/filename.jpg` и использовался напрямую как `src` в Vue-компонентах. Браузер запрашивал `/products/filename.jpg`, которого нет — файлы лежат в `/storage/products/`.

**Решение**: Eloquent accessor `mainImage()` в модели `Product` преобразует путь в полный URL через `Storage::disk('public')->url($value)`. Все Vue-компоненты получают уже готовый `https://asoft.kz/storage/products/...` без изменений на стороне фронтенда.

### Лицензии не отображались в таблице и форме редактирования

**Проблема**: метод `adminIndex()` делал `->with(['category', 'vendor'])` без `'licenses'`. Vue получал объекты товаров без массива лицензий — таблица всегда показывала «Нет», форма открывалась пустой.

**Решение**: добавлено `'licenses'` в eager loading: `->with(['category', 'vendor', 'licenses'])`.

### Поле «Цена от» в форме товара было избыточным

**Проблема**: администратор вводил `price_from` вручную, но это значение должно совпадать с минимальной ценой лицензии — дублирование с риском ошибки.

**Решение**: поле удалено из формы. После синхронизации лицензий метод `syncLicenses()` автоматически обновляет `price_from`:
```php
$product->load('licenses');
$minPrice = $product->licenses->min('price');
$product->update(['price_from' => $minPrice]);
```

---

## Известные ограничения и точки роста

- Лицензионные ключи (`order_items.license_key`) хранятся в БД, но механизм их генерации/выдачи после оплаты не реализован.
- Email-уведомления не настроены.
- Платёжная интеграция отсутствует (статусы меняются вручную через admin-панель).
