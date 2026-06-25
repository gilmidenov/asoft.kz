# Документация по коду — Atlas Software (asoft.kz)

> **Актуальность:** обновлено 2026-06-25, отражает полный текущий код.

---

## Архитектура проекта

```
Браузер (Vue 3 SPA) ──HTTP/JSON──> Laravel 13 REST API ──SQL──> PostgreSQL 16
                    <──JSON────────                    <─────────
```

**Backend**: Laravel 13 (PHP 8.4-fpm) — только REST API, отдаёт JSON  
**Frontend**: Vue 3 + Pinia + Vue Router + Tailwind CSS, собирается Vite  
**База данных**: PostgreSQL 16  
**Аутентификация**: Laravel Sanctum (Bearer-токены в localStorage)  
**Инфраструктура**: Docker Compose (nginx:alpine, php:8.4-fpm, postgres:16-alpine)  
**Публичные файлы**: `storage/app/public/` → симлинк `public/storage/`

---

## Структура таблиц БД

```
users
  └── orders ──── order_items ──── products
                                       ├── categories (parent_id → self)
                                       ├── vendors
                                       ├── product_licenses
                                       └── product_images

cart_items ──── users + products + product_licenses
favorites  ──── users + products

banners           # рекламные баннеры главной страницы
pages             # разделы компании (О компании, Новости и т.д.)
page_items        # элементы разделов (статьи, PDF, изображения)
```

---

## Backend (Laravel API)

### Маршруты (`routes/api.php`)

Все маршруты имеют префикс `/api` (настроено в `bootstrap/app.php`).

#### Публичные (без авторизации)

| Метод | URL | Контроллер::метод |
|-------|-----|-------------------|
| GET | `/api/categories` | `CategoryController::index` |
| GET | `/api/categories/{slug}` | `CategoryController::show` |
| GET | `/api/vendors` | `VendorController::index` |
| GET | `/api/vendors/{slug}` | `VendorController::show` |
| GET | `/api/products` | `ProductController::index` |
| GET | `/api/products/{slug}` | `ProductController::show` |
| GET | `/api/banners` | `BannerController::index` |
| GET | `/api/pages` | `PageController::index` |
| GET | `/api/pages/{slug}` | `PageController::show` |
| GET/POST/PATCH/DELETE | `/api/cart` | `CartController::*` (middleware `auth.optional`) |
| POST | `/api/auth/register` | `AuthController::register` |
| POST | `/api/auth/login` | `AuthController::login` |

#### Защищённые (`middleware('auth:sanctum')`)

| Метод | URL | Контроллер::метод |
|-------|-----|-------------------|
| POST | `/api/auth/logout` | `AuthController::logout` |
| GET | `/api/auth/me` | `AuthController::me` |
| PATCH | `/api/auth/me` | `AuthController::update` |
| GET/POST | `/api/favorites` | `FavoriteController::*` |
| GET/POST | `/api/orders` | `OrderController::*` |
| GET | `/api/orders/{id}` | `OrderController::show` |

#### Только для admin (`middleware('role:admin')`, префикс `/api/admin`)

| Метод | URL | Описание |
|-------|-----|----------|
| CRUD | `/admin/categories` | Управление категориями |
| POST | `/admin/categories/{id}/image` | Загрузка изображения категории |
| CRUD | `/admin/vendors` | Управление вендорами |
| POST | `/admin/vendors/{id}/image` | Загрузка логотипа вендора |
| GET | `/admin/products` | Все товары (любой статус) |
| CRUD | `/admin/products` | Управление товарами |
| POST | `/admin/products/{id}/image` | Загрузка изображения товара |
| DELETE | `/admin/products/{id}/image` | Удаление изображения товара |
| POST | `/admin/products/{id}/licenses/sync` | Синхронизация лицензий |
| GET | `/admin/orders` | Все заказы |
| PATCH | `/admin/orders/{id}/status` | Смена статуса заказа |
| GET/POST/PUT/DELETE | `/admin/banners` | Управление баннерами |
| POST | `/admin/banners/{id}/image` | Загрузка изображения баннера |
| GET/POST/PUT/DELETE | `/admin/pages` | Управление разделами компании |
| GET/POST | `/admin/pages/{id}/items` | Элементы раздела |
| PUT/DELETE | `/admin/items/{id}` | Обновление/удаление элемента |
| POST | `/admin/items/{id}/file` | Загрузка файла к элементу |

---

### Контроллеры (`app/Http/Controllers/Api/`)

#### `AuthController`

| Метод | Описание |
|-------|----------|
| `register` | Валидирует поля, создаёт User, генерирует Sanctum-токен, возвращает `{user, token}` |
| `login` | Проверяет email+password через `Hash::check`, возвращает `{user, token}` |
| `logout` | Удаляет текущий токен через `$request->user()->currentAccessToken()?->delete()`. Знак `?->` защищает от null (токен может быть уже удалён) |
| `me` | Возвращает аутентифицированного пользователя |
| `update` | Обновляет `name` и `phone` текущего пользователя |

**Как работают токены Sanctum:**
```php
$token = $user->createToken('auth-token')->plainTextToken;
// plainTextToken — строка вида "1|abcdef..." — это Bearer-токен для клиента
// Хранится хэшированным в таблице personal_access_tokens
```

---

#### `ProductController`

**Публичный листинг** (`index`): выбирает только `status = 'active'`, применяет цепочку условий через `when()`:

```php
->when($search, fn($q) => $q->where(function($q) use ($search) {
    $driver = DB::connection()->getDriverName();
    $op = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';   // PostgreSQL нечувствителен к регистру через ILIKE
    $q->where('name', $op, "%$search%")
      ->orWhere('short_description', $op, "%$search%");
}))
->when($category, fn($q) => $q->whereHas('category', fn($q2) => $q2->where('slug', $category)))
->when($vendor,   fn($q) => $q->whereHas('vendor',   fn($q2) => $q2->where('slug', $vendor)))
->when($isHit,    fn($q) => $q->where('is_hit', true))
->when($isSale,   fn($q) => $q->where('is_sale', true))
```

Сортировка через `match`:
```php
match($sort) {
    'price_asc'  => $query->orderBy('price_from', 'asc'),
    'price_desc' => $query->orderBy('price_from', 'desc'),
    'name_asc'   => $query->orderBy('name', 'asc'),
    'new'        => $query->orderBy('created_at', 'desc'),
    default      => $query->orderBy('sort_order'),
}
```

**Синхронизация лицензий** (`syncLicenses`): 
1. `$product->licenses()->delete()` — удаляет все старые лицензии
2. `$product->licenses()->createMany($licenses)` — создаёт новые из массива
3. Автоматически пересчитывает `price_from = MIN(price среди лицензий)` — это значение используется в каталоге как «от X ₸»

**Загрузка изображений** (`uploadImage`):
- Старый файл удаляется через `Storage::disk('public')->delete($path)`
- Новый сохраняется в `products/` папке публичного диска
- Accessor на модели преобразует путь в URL автоматически

---

#### `CategoryController`

- `index`: только активные корневые категории (где `parent_id IS NULL`) с `with('children')`. Возвращает дерево одним запросом (eager loading избегает N+1).
- `show`: категория + `children` + `parent` — для breadcrumb на странице каталога.
- `store`/`update`: slug генерируется из `Str::slug($name)` — кириллица транслитерируется в латиницу.
- `uploadImage`: файл сохраняется в `storage/app/public/categories/`, старый удаляется. Accessor на модели возвращает полный URL.

---

#### `VendorController`

Аналогичен CategoryController. `logo` хранится в `vendors/`, accessor на модели `Vendor` возвращает полный URL.

---

#### `BannerController`

Управляет рекламными баннерами главной страницы.

| Метод | Маршрут | Описание |
|-------|---------|----------|
| `index` | `GET /api/banners` | Активные баннеры, сортировка по `sort_order` |
| `adminIndex` | `GET /api/admin/banners` | Все баннеры (включая скрытые) |
| `store` | `POST /api/admin/banners` | Создать баннер (без файла) |
| `update` | `PUT /api/admin/banners/{id}` | Обновить текст/настройки |
| `destroy` | `DELETE /api/admin/banners/{id}` | Удалить баннер + файл с диска |
| `uploadImage` | `POST /api/admin/banners/{id}/image` | Загрузить/заменить фото баннера |

**Алгоритм удаления файла:**
```php
$raw = $banner->getRawOriginal('image');   // берём путь ДО обработки accessor'а
if ($raw && !str_starts_with($raw, 'http')) {
    Storage::disk('public')->delete($raw);  // удаляем только локальные файлы
}
```
`getRawOriginal()` нужен потому что accessor `image()` возвращает полный URL, а не относительный путь для `Storage::delete()`.

---

#### `PageController`

Управляет разделами компании (О компании, Новости, Проекты и т.д.) и их элементами.

**Разделы:**

| Метод | Маршрут | Описание |
|-------|---------|----------|
| `index` | `GET /api/pages` | Активные разделы для навигационного меню |
| `show` | `GET /api/pages/{slug}` | Раздел + все активные элементы (`with('items')`) |
| `adminIndex` | `GET /api/admin/pages` | Все разделы с `withCount('allItems')` |
| `storePage` | `POST /api/admin/pages` | Создать раздел (slug из Str::slug(title)), max:40 символов |
| `updatePage` | `PUT /api/admin/pages/{id}` | Обновить раздел, max:40 символов |
| `destroyPage` | `DELETE /api/admin/pages/{id}` | Удалить раздел + все элементы (cascade) |

**Элементы раздела:**

| Метод | Маршрут | Описание |
|-------|---------|----------|
| `adminItems` | `GET /api/admin/pages/{id}/items` | Все элементы раздела (включая скрытые) |
| `storeItem` | `POST /api/admin/pages/{id}/items` | Создать элемент |
| `updateItem` | `PUT /api/admin/items/{id}` | Обновить метаданные элемента |
| `destroyItem` | `DELETE /api/admin/items/{id}` | Удалить элемент + файл |
| `uploadFile` | `POST /api/admin/items/{id}/file` | Загрузить изображение или PDF |

**Определение типа файла при загрузке:**
```php
$mime     = $uploadedFile->getMimeType();
$fileType = str_contains($mime, 'pdf') ? 'pdf' : 'image';
$folder   = $fileType === 'pdf' ? 'page-docs' : 'page-images';
```
MIME-тип определяется автоматически — администратору не нужно выбирать тип вручную.

---

#### `CartController`

Корзина работает для двух типов пользователей:
- **Авторизованные**: позиции привязаны к `user_id`
- **Гости**: позиции привязаны к `session_id` из заголовка `X-Session-Id`

Middleware `auth.optional` (кастомный) не требует авторизации, но если токен передан — аутентифицирует пользователя. Это позволяет одному контроллеру обслуживать оба сценария.

---

#### `OrderController`

**Создание заказа** (`store`) выполняется в транзакции:
```php
DB::transaction(function () use ($request, $user) {
    $order = Order::create([...]);
    foreach ($cartItems as $item) {
        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $item->product->id,
            'product_name'   => $item->product->name,    // КОПИЯ на момент покупки
            'license_name'   => $item->license->name,    // КОПИЯ — не меняется при обновлении товара
            'price'          => $item->license->price,   // КОПИЯ цены
            'quantity'       => $item->quantity,
        ]);
    }
    $cart->clear();
});
```
Копирование `product_name` и `license_name` принципиально: если продавец изменит цену или название через 6 месяцев, история заказов останется корректной.

**Статусы**: `pending` → `paid` → `processing` → `completed` | `cancelled` | `refunded`

---

### Модели (`app/Models/`)

#### `User`

```
Поля: name, email, password, phone, role (customer|admin)
Связи: hasMany Orders, hasMany Favorites, hasMany CartItems
```

#### `Product`

```
Поля: name, slug, short_description, description, category_id, vendor_id,
      status (active|inactive|out_of_stock), is_hit, is_new, is_sale,
      price_from, stock_quantity, main_image, views_count, sort_order
Связи: belongsTo Category, belongsTo Vendor
       hasMany ProductLicense, hasMany ProductImage
Accessor mainImage(): Storage::disk('public')->url($value)
  → преобразует 'products/file.jpg' в 'https://asoft.kz/storage/products/file.jpg'
```

#### `Category`

```
Поля: name, slug, parent_id, description, image, sort_order, is_active
Связи: belongsTo Category (parent), hasMany Category (children), hasMany Product
Accessor image(): аналогичен mainImage — путь → полный URL
```

#### `Vendor`

```
Поля: name, slug, description, website, logo, is_active
Accessor logo(): путь → полный URL
```

#### `ProductLicense`

```
Поля: product_id, name, price, old_price, type (perpetual|subscription|corporate),
      devices, duration_months, in_stock, sort_order
Связь: belongsTo Product
```

#### `CartItem`

```
Поля: user_id (nullable), session_id (nullable), product_id, product_license_id, quantity
Связи: belongsTo Product, belongsTo ProductLicense
```

#### `Order` / `OrderItem`

```
Order: order_number (уникальный), user_id, status, total, customer_name,
       customer_email, customer_phone
OrderItem: order_id, product_id, product_name, license_name, price, quantity
  → product_name и license_name — КОПИИ на момент покупки, не меняются
```

#### `Banner` *(новая)*

```
Поля: title, subtitle, button_text, button_url, image, sort_order, is_active
Accessor image(): путь → полный URL (null если изображения нет)
Назначение: рекламные слайды на главной странице
```

#### `Page` *(новая)*

```
Поля: title, slug, description, sort_order, is_active
Связи:
  items()    → hasMany PageItem, только активные, отсортированные по sort_order
  allItems() → hasMany PageItem, все (для admin)
Назначение: разделы компании («О компании», «Новости» и т.д.)
```

#### `PageItem` *(новая)*

```
Поля: page_id, title, content, file_path, file_type (image|pdf|text), sort_order, is_active
Связь: belongsTo Page
Accessor filePath(): путь → полный URL
Назначение: карточки контента внутри раздела компании
```

---

### Middleware

#### `CheckRole` (`app/Http/Middleware/CheckRole.php`)

Проверяет роль текущего пользователя. Подключён как алиас `role` в `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $m) {
    $m->alias(['role' => CheckRole::class]);
})
```

Использование в маршрутах: `Route::middleware('role:admin')`. Возвращает HTTP 403 если `$user->role !== $requiredRole`.

#### `OptionalAuth` (кастомный, для корзины)

Регистрируется как `auth.optional`. Пытается аутентифицировать пользователя через Bearer-токен, но не блокирует запрос если токена нет (гости также проходят).

---

### Сидеры (`database/seeders/`)

| Файл | Что заполняет |
|------|---------------|
| `DatabaseSeeder.php` | Создаёт admin-пользователя, вызывает остальные сидеры |
| `CategorySeeder.php` | Примеры категорий ПО (Office, Антивирусы, и т.д.) |
| `VendorSeeder.php` | Примеры вендоров (Microsoft, Kaspersky, Adobe, и т.д.) |
| `ProductSeeder.php` | Примеры товаров с лицензиями |
| `PageSeeder.php` | 9 разделов компании (О компании, Новости, Проекты, ...) |

```php
// PageSeeder — firstOrCreate гарантирует идемпотентность:
// повторный запуск не создаёт дубликатов
Page::firstOrCreate(['slug' => 'about'], ['title' => 'О компании', 'sort_order' => 1, 'is_active' => true]);
```

---

## Frontend (Vue 3)

### Структура файлов

```
resources/js/
├── App.vue                          # Корневой компонент — загружает user + корзину при старте
├── main.js                          # Инициализация Vue, Pinia, Router, Axios (baseURL + интерсептор токена)
├── router/
│   └── index.js                     # Все маршруты + навигационный guard
├── stores/
│   ├── auth.js                      # user, token, login/logout/register/fetchUser
│   ├── cart.js                      # корзина, count (computed)
│   └── catalog.js                   # категории + вендоры: кэш + методы принудительного обновления
├── pages/
│   ├── HomePage.vue                 # Главная: BannerSlider + категории + хиты + новинки + преимущества
│   ├── CatalogPage.vue              # Каталог с сайдбаром фильтров и пагинацией
│   ├── ProductPage.vue              # Страница товара: лицензии, изображения, описание
│   ├── CartPage.vue                 # Корзина
│   ├── CheckoutPage.vue             # Оформление заказа
│   ├── LoginPage.vue                # Вход
│   ├── RegisterPage.vue             # Регистрация
│   ├── AccountPage.vue              # Личный кабинет + история заказов
│   ├── FavoritesPage.vue            # Избранные товары
│   ├── VendorsPage.vue              # Список вендоров (сетка карточек)
│   ├── VendorPage.vue               # Страница вендора: карточка + товары вендора
│   ├── CompanyPage.vue              # Страница раздела компании (мини-каталог элементов)
│   ├── NotFoundPage.vue             # 404
│   └── admin/
│       ├── AdminLayout.vue          # Layout с боковым меню для всех admin-страниц
│       ├── DashboardPage.vue        # Дашборд со статистикой
│       ├── ProductsPage.vue         # CRUD товаров с лицензиями и изображениями
│       ├── CategoriesPage.vue       # CRUD категорий с изображениями
│       ├── VendorsPage.vue          # CRUD вендоров с логотипами
│       ├── OrdersPage.vue           # Управление заказами, смена статусов
│       ├── BannersPage.vue          # CRUD баннеров главной страницы
│       └── PagesPage.vue            # Управление разделами компании и их элементами
└── components/
    ├── layout/
    │   ├── AppHeader.vue            # Header: 3 полосы — dark(лого+телефон+кабинет), white(каталог+поиск), slate(разделы компании)
    │   └── AppFooter.vue            # Footer: контакты, адрес, ссылки
    ├── catalog/
    │   └── ProductCard.vue          # Карточка товара в сетке каталога
    └── ui/
        ├── BasePagination.vue       # Компонент пагинации
        └── BannerSlider.vue         # Слайдер баннеров для главной страницы
```

---

### Маршруты (`router/index.js`)

| Путь | Название | Компонент | Доступ |
|------|----------|-----------|--------|
| `/` | `home` | `HomePage` | публичный |
| `/catalog` | `catalog` | `CatalogPage` | публичный |
| `/catalog/:slug` | `category` | `CatalogPage` | публичный |
| `/product/:slug` | `product` | `ProductPage` | публичный |
| `/cart` | `cart` | `CartPage` | публичный |
| `/vendors` | `vendors` | `VendorsPage` | публичный |
| `/vendors/:slug` | `vendor` | `VendorPage` | публичный |
| `/company/:slug` | `company-page` | `CompanyPage` | публичный |
| `/login` | `login` | `LoginPage` | `guest` (редирект→ /account если авторизован) |
| `/register` | `register` | `RegisterPage` | `guest` |
| `/checkout` | `checkout` | `CheckoutPage` | `requiresAuth` |
| `/account` | `account` | `AccountPage` | `requiresAuth` |
| `/favorites` | `favorites` | `FavoritesPage` | `requiresAuth` |
| `/admin` | `admin` | `AdminLayout` + `DashboardPage` | `requiresAuth + requiresAdmin` |
| `/admin/products` | `admin-products` | `ProductsPage` | admin |
| `/admin/categories` | `admin-categories` | `CategoriesPage` | admin |
| `/admin/vendors` | `admin-vendors` | `VendorsPage` | admin |
| `/admin/orders` | `admin-orders` | `OrdersPage` | admin |
| `/admin/banners` | `admin-banners` | `BannersPage` | admin |
| `/admin/pages` | `admin-pages` | `PagesPage` | admin |

**Навигационный guard** (`router.beforeEach`):
```js
// Читает token и role из localStorage — не делает HTTP-запросов
const token           = localStorage.getItem('auth_token')
const role            = localStorage.getItem('auth_role')
const isAuthenticated = !!token
const isAdmin         = role === 'admin'

if (to.meta.requiresAuth && !isAuthenticated) → redirect /login
if (to.meta.requiresAdmin && !isAdmin)        → redirect /
if (to.meta.guest && isAuthenticated)         → redirect /account
```

---

### Pinia-сторы (`stores/`)

#### `auth.js`

```js
// Состояние (reactive refs):
token           // string | null — из localStorage, восстанавливается при перезагрузке
user            // object | null — данные пользователя (name, email, role)

// Computed:
isAuthenticated // computed: !!token.value
isAdmin         // computed: user.value?.role === 'admin'

// Методы:
login(email, password)        // POST /api/auth/login → сохраняет token + role в localStorage
register(name, email, ...)    // POST /api/auth/register → аналогично
logout()                      // POST /api/auth/logout → очищает localStorage + состояние
fetchUser()                   // GET /api/auth/me → вызывается в App.vue при монтировании
```

#### `cart.js`

```js
items   // ref: массив позиций корзины
count   // computed: общее количество товаров (sum of quantities)

// При монтировании App.vue вызывает fetchCart():
// GET /api/cart с заголовком X-Session-Id (для гостей)
```

#### `catalog.js`

```js
categories  // ref: массив категорий с children
vendors     // ref: массив вендоров

// Методы (с кэшированием):
fetchCategories()   // GET /api/categories — загружает только если categories пусты
fetchVendors()      // GET /api/vendors   — аналогично

// Методы принудительного обновления (без кэша):
refreshCategories() // используется в CategoriesPage.vue после CRUD
refreshVendors()    // используется в VendorsPage.vue после CRUD
```

---

### Компоненты

#### `AppHeader.vue`

Три вложенных полосы внутри `<header class="sticky top-0 z-50">`:

**Полоса 1 — тёмная (`bg-header`):**
- Логотип (RouterLink → `/`)
- Номер телефона (`tel:+77075973777`)
- Корзина (иконка + счётчик `cartStore.count`, RouterLink → `/cart`)
- Сердечко — избранное (RouterLink → `/favorites`)
- Кнопка «Выйти» / ссылка «Войти» (зависит от `authStore.isAuthenticated`)
- Ссылка «Админ» (желтая, только если `authStore.isAdmin`)

**Полоса 2 — белая (`bg-white`):**
- Кнопка «Каталог» с выпадающим меню категорий:
  ```js
  catalogMenuOpen = ref(false)
  // v-if="catalogMenuOpen" — показывает список категорий
  // @click на оверлее — закрывает меню
  ```
- Поле поиска (form @submit.prevent → router.push к каталогу с query.search)
- Быстрые ссылки: Вендоры, Хиты, Акции

**Полоса 3 — тёмно-синяя (`bg-slate-700`) — карусель разделов компании:**

Карусель, а не прокрутка скроллом — стрелки управляют видимой областью:

```js
const navRef   = ref(null)   // ref на DOM-элемент прокручиваемого контейнера
const showPrev = ref(false)  // показывать ли стрелку «назад»
const showNext = ref(false)  // показывать ли стрелку «вперёд»

function updateArrows() {
    const el = navRef.value
    if (!el) return
    showPrev.value = el.scrollLeft > 2
    showNext.value = el.scrollLeft < el.scrollWidth - el.clientWidth - 2
}

function scrollNav(amount) {
    navRef.value?.scrollBy({ left: amount, behavior: 'smooth' })
    setTimeout(updateArrows, 350)  // обновляем после завершения плавной прокрутки
}

// Пересчитываем стрелки когда разделы загрузились
watch(companyPages, async () => { await nextTick(); updateArrows() })
```

Нативный скроллбар скрыт через CSS (файл scoped):
```css
.nav-carousel::-webkit-scrollbar { display: none; }
.nav-carousel { -ms-overflow-style: none; scrollbar-width: none; }
```
`overflow-x-auto` оставляет JS-прокрутку через `scrollBy()`, но скрывает полосу прокрутки.

Стрелки `v-show="showPrev/showNext"` — появляются динамически: левая — когда есть контент слева, правая — когда есть контент справа. На широких мониторах все 9 разделов помещаются без стрелок.

Валидация названий разделов: **максимум 40 символов** (и на фронтенде через `maxlength="40"`, и на бэкенде через `'title' => 'max:40'`). Счётчик символов в форме подсвечивается оранжевым при >35 символах.

#### `BannerSlider.vue`

Компонент слайдера для главной страницы. Загружает баннеры из `/api/banners`.

```js
const banners = ref([])
const current = ref(0)
let   timer   = null

// Базовый корпоративный слайд — ВСЕГДА первый в ротации.
// Пользовательские баннеры добавляются ПОСЛЕ него.
const fallback = { title: 'Лицензионное программное...', subtitle: '...', ... }

// slides = [fallback, ...banners.value]
// → 0 пользовательских: [fallback]     — нет стрелок, статичный слайд
// → 1 пользовательский: [fallback, b1] — стрелки появляются, можно переключать
// → 2+: [fallback, b1, b2, ...]
const slides = computed(() => [fallback, ...banners.value])
```

Такая схема гарантирует: основное корпоративное сообщение всегда в ротации, а добавленные баннеры расширяют её.

**Автопрокрутка:**
```js
function startTimer() {
    if (slides.value.length < 2) return  // один слайд — таймер не нужен
    timer = setInterval(() => {
        current.value = (current.value + 1) % slides.value.length
    }, 5000)
}
onMounted(async () => { await axios.get('/banners'); startTimer() })
onUnmounted(stopTimer)  // чистим таймер — иначе будет утечка памяти
```

**Рендер слайдов — CSS cross-fade (НЕ transition-group):**

Все слайды одновременно в DOM, перекрываются через `absolute inset-0`. Видимость через `opacity` + `z-index` — это правильный подход для cross-fade:

```vue
<div v-for="(slide, idx) in slides" :key="idx"
     :class="[
         'absolute inset-0 transition-opacity duration-700',
         idx === current ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'
     ]">
```

Почему НЕ `<transition-group>` + `v-show`: `transition-group` предназначен для элементов, входящих/выходящих из DOM (v-if). С `v-show` + `display:none` переходы не работают корректно. Подход с `opacity` + `pointer-events-none` надёжнее и проще.

`pointer-events-none` на невидимых слайдах исключает случайные клики сквозь них.

**Управление:** стрелки `prev`/`next`, точки-индикаторы, пауза по `@mouseenter`, возобновление по `@mouseleave`.

#### `CompanyPage.vue`

Страница раздела компании (`/company/:slug`).

```js
// Watch slug в URL — перезагружает при переходе между разделами
// без пересоздания компонента (Vue Router переиспользует компонент для того же маршрута)
watch(() => route.params.slug, (slug) => { if (slug) loadPage(slug) }, { immediate: true })

async function loadPage(slug) {
    const { data } = await axios.get(`/pages/${slug}`)  // GET /api/pages/{slug}
    page.value = data  // { id, title, description, items: [...] }
}
```

**Отображение элементов** — сетка карточек (grid-cols-2/3/4):
- `file_type === 'image'` → превью изображения + `group-hover:scale-105`
- `file_type === 'pdf'`   → красная иконка PDF
- `file_type === 'text'`  → синий фон с заголовком элемента
- Клик по карточке с файлом → открывает **лайтбокс**

**Лайтбокс** (`<Teleport to="body">`):
- Изображение: `<img>` во весь контейнер
- PDF: `<iframe>` + кнопка «Скачать PDF» (ссылка с атрибутом `download`)
- Закрытие: клик на фон или кнопку `×`

#### `ProductCard.vue`

Карточка товара в сетке. Показывает: изображение, название, цену от `price_from`, бейджи (Хит, Акция, Новинка), кнопки «В корзину» и «В избранное».

#### `BasePagination.vue`

Принимает `props: { currentPage, lastPage }`, эмитит `@page-changed(newPage)`. Показывает номера страниц с многоточием при большом количестве.

---

### Страницы

#### `HomePage.vue`

Секции в порядке:
1. **BannerSlider** — слайдер с баннерами (ранее — статичный hero-блок)
2. **Категории** — сетка карточек из `catalogStore.categories`
3. **Хиты продаж** — загружает `GET /api/products?is_hit=1&per_page=8`
4. **Новинки** — загружает `GET /api/products?sort=new&per_page=8`
5. **Преимущества** — статичный блок 4 карточки (мгновенная доставка, лицензии, поддержка, цены)

#### `CatalogPage.vue`

Компонент используется для `/catalog` и `/catalog/:slug` одновременно — slug берётся из `route.params.slug`.

**Фильтры** (реактивный объект):
```js
const filters = ref({
    search, category, vendor, sort, price_from, price_to, is_hit, is_sale, page
})
```

Все фильтры отражаются в URL (query-параметры). При изменении фильтра URL обновляется через `router.replace()`, watcher на URL перезагружает список.

**Сайдбар:**
- Dropdown подкатегорий (если у выбранной категории есть дети или братья)
- Фильтры цены (`price_from`, `price_to`)
- Флаги: только хиты, только акции

#### `VendorPage.vue`

Загружает вендора из `GET /api/vendors/:slug`, затем его товары из `GET /api/products?vendor=:slug`. Breadcrumb: Главная / Вендоры / [Имя вендора].

#### `admin/BannersPage.vue`

Таблица баннеров с превью (изображение или плейсхолдер-градиент).

**Создание/редактирование:**
1. Сначала сохраняются текстовые поля (`POST /admin/banners` или `PUT /admin/banners/:id`)
2. Если выбрано изображение — загружается отдельным запросом `POST /admin/banners/:id/image` (FormData, multipart)

Два-этапный подход нужен потому что `id` баннера известен только после создания.

#### `admin/PagesPage.vue`

**Два режима в одном компоненте:**

1. **Список разделов** (`selectedPage === null`): таблица всех разделов, колонки: Название, Slug, Элементов, Порядок, Статус. Кнопка «Контент» → переключает в режим элементов.

2. **Элементы раздела** (`selectedPage !== null`): сетка карточек с превью файла. Кнопка «←» → возврат к списку разделов.

При переключении `selectPage(page)` вызывает `GET /api/admin/pages/:id/items`.

**Загрузка файла к элементу** определяет тип по MIME автоматически:
```js
function onFilePicked(e) {
    const file = e.target.files[0]
    if (file.type.startsWith('image/')) {
        itemForm.value.file_type = 'image'  // показывает превью
    } else if (file.type === 'application/pdf') {
        itemForm.value.file_type = 'pdf'    // показывает иконку PDF
    }
}
```

#### `admin/AdminLayout.vue`

Sidebar с двумя секциями:

**Магазин:** Дашборд, Товары, Категории, Вендоры, Заказы  
**Контент:** Баннеры, Разделы

Каждая ссылка использует `active-class="bg-white/10"` для подсветки активного пункта.

---

### Axios настройка (`main.js`)

```js
axios.defaults.baseURL = '/api'   // все запросы идут на /api/...

// Интерсептор запроса — автоматически добавляет Bearer-токен
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

// Благодаря этому во всех компонентах можно просто писать:
axios.get('/products')   // без Authorization: Bearer ...
// Интерсептор добавит заголовок автоматически
```

---

## Хранилище файлов

**Диск**: `public` (`storage/app/public/`) → символическая ссылка `public/storage/`

| Папка | Содержимое |
|-------|------------|
| `categories/` | Изображения категорий |
| `vendors/` | Логотипы вендоров |
| `products/` | Изображения товаров |
| `banners/` | Фотографии баннеров |
| `page-images/` | Изображения в разделах компании |
| `page-docs/` | PDF документы в разделах компании |

**Accessor-паттерн** (используется во всех моделях с файлами):
```php
protected function image(): Attribute
{
    return Attribute::make(
        get: fn($value) => $value
            ? (str_starts_with($value, 'http') ? $value   // внешний URL — оставляем как есть
                : Storage::disk('public')->url($value))    // локальный путь → полный URL
            : null,
    );
}
```

---

## Тесты (`tests/Feature/`)

```bash
docker exec asoft_app php artisan test
docker exec asoft_app php artisan test --testdox         # читаемые имена тестов
docker exec asoft_app php artisan test tests/Feature/AuthTest.php
```

| Файл | Покрытие |
|------|----------|
| `AuthTest.php` | register, login, logout, me, update |
| `ProductTest.php` | index, search, filters, admin CRUD, syncLicenses |
| `CategoryTest.php` | index, show, admin CRUD, иерархия parent/children |
| `OrderTest.php` | store (транзакция), расчёт суммы, adminIndex, updateStatus |

**Конфигурация** (`phpunit.xml`): SQLite в памяти (`DB_DATABASE=:memory:`).  
Поиск использует `ILIKE` в PostgreSQL и `LIKE` в SQLite — переключение автоматическое через `DB::connection()->getDriverName()`.

---

## Известные исправления

### OPcache игнорировал изменения файлов
`docker/php/prod.ini`: `opcache.validate_timestamps = 0` → исправлено на `1`. PHP теперь замечает изменения без перезапуска контейнера.

### Изображения возвращали 404
`main_image` хранился как `products/filename.jpg` и использовался напрямую как `src`. Исправлено через Eloquent accessor `mainImage()` — возвращает полный Storage URL.

### Лицензии не отображались в форме редактирования
`adminIndex()` не включал `'licenses'` в eager loading. Исправлено: `->with(['category', 'vendor', 'licenses'])`.

### Новые категории/вендоры не появлялись без перезагрузки
`catalog.js` кэшировал данные с охранником `if (categories.value.length) return`. Исправлено: добавлены методы `refreshCategories()` / `refreshVendors()`, которые вызываются в CategoriesPage/VendorsPage после каждой CRUD-операции.

### Фильтр по вендору не работал при переходе с VendorsPage
`CatalogPage.vue` инициализировал `filters` без поля `vendor`. Исправлено: добавлено `vendor: route.query.vendor || ''` в начальное состояние и в query-параметры API-запроса.

---

## Ограничения и точки роста

- Лицензионные ключи (`order_items.license_key`) в схеме есть, механизм выдачи после оплаты не реализован
- Email-уведомления не настроены (MAIL_MAILER=log)
- Платёжная интеграция отсутствует — статусы меняются вручную через admin
- Баннер-слайдер не поддерживает swipe на мобильных (только стрелки и точки)
