# Часть 5: Миграции и модели {#часть-5}

## Что такое миграция

**Миграция** — это PHP-файл, который описывает изменение схемы базы данных. Вместо того чтобы запускать SQL вручную, мы пишем PHP-код, который Laravel выполняет командой `php artisan migrate`.

**Преимущества:**
- Схема БД хранится в Git вместе с кодом
- Можно откатить изменения командой `php artisan migrate:rollback`
- Вся команда работает с одинаковой схемой

## 5.1 Расширение таблицы users

Laravel уже создал миграцию для `users`. Нам нужно добавить поле `role` и `phone`.

Создадим новую миграцию для модификации:

```bash
php artisan make:migration add_fields_to_users_table
```

**Что происходит:** Artisan создаёт файл `database/migrations/2024_xx_xx_xxxxxx_add_fields_to_users_table.php`. Имя файла начинается с timestamp — это гарантирует правильный порядок выполнения.

Открой созданный файл и замени его содержимое:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Все миграции — это классы, наследующие Migration
return new class extends Migration
{
    // up() выполняется при php artisan migrate (применить изменения)
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // После столбца 'name' добавляем 'phone'
            // nullable() — поле необязательное (может быть NULL)
            $table->string('phone', 20)->nullable()->after('name');

            // role определяет права пользователя:
            // 'customer' — обычный покупатель
            // 'admin'    — администратор магазина
            // default('customer') — новые пользователи по умолчанию покупатели
            $table->enum('role', ['customer', 'admin'])->default('customer')->after('phone');
        });
    }

    // down() выполняется при php artisan migrate:rollback (откатить)
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем столбцы в обратном порядке
            $table->dropColumn(['phone', 'role']);
        });
    }
};
```

---

## 5.2 Миграция: categories (Категории)

```bash
php artisan make:migration create_categories_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // id() — автоинкрементный первичный ключ (bigint unsigned)
            $table->id();

            // Название категории: "Офисное ПО", "Антивирусы"
            $table->string('name');

            // slug — URL-friendly версия имени: "ofisnoye-po", "antivirusy"
            // Используется в URL: /catalog/ofisnoye-po
            // unique() — слаг должен быть уникальным
            $table->string('slug')->unique();

            // parent_id — для вложенных категорий.
            // NULL = категория верхнего уровня
            // Если parent_id = 5, значит это подкатегория категории с id=5
            // nullable() — может быть NULL (корневые категории)
            // constrained() — добавляет внешний ключ на таблицу categories.id
            // onDelete('set null') — если родительская категория удалена, 
            //                        у дочерних parent_id становится NULL
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('set null');

            // Описание категории (необязательное)
            $table->text('description')->nullable();

            // Путь к иконке или изображению категории
            $table->string('image')->nullable();

            // Порядок сортировки. Категории с меньшим числом показываются первыми
            $table->unsignedInteger('sort_order')->default(0);

            // is_active — показывать ли категорию на сайте
            $table->boolean('is_active')->default(true);

            // timestamps() создаёт два столбца:
            // created_at — когда запись создана
            // updated_at — когда запись последний раз изменена
            // Laravel обновляет их автоматически
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // dropIfExists() — удаляет таблицу, если она существует
        Schema::dropIfExists('categories');
    }
};
```

---

## 5.3 Миграция: vendors (Вендоры/Производители)

```bash
php artisan make:migration create_vendors_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // Полное название компании: "Microsoft Corporation"
            $table->string('name');

            $table->string('slug')->unique();

            // Краткое название для отображения: "Microsoft"
            $table->string('short_name')->nullable();

            // Логотип вендора
            $table->string('logo')->nullable();

            // Описание компании
            $table->text('description')->nullable();

            // Сайт вендора
            $table->string('website')->nullable();

            // Страна происхождения: "США", "Россия", "Казахстан"
            $table->string('country')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
```

---

## 5.4 Миграция: products (Товары)

```bash
php artisan make:migration create_products_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Название программы: "Microsoft Office 2024 Home"
            $table->string('name');

            $table->string('slug')->unique();

            // Краткое описание — для карточки в каталоге
            $table->string('short_description')->nullable();

            // Полное описание — для страницы товара (HTML-текст)
            $table->longText('description')->nullable();

            // Связь с категорией.
            // foreignId('category_id') — создаёт столбец category_id bigint unsigned
            // constrained() — добавляет внешний ключ на categories.id
            // onDelete('set null') — если категорию удалят, у товара category_id = NULL
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // Связь с вендором
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // Версия программы: "2024", "365", "11"
            $table->string('version')->nullable();

            // Язык программы: "Русский", "Мультиязычный"
            $table->string('language')->nullable();

            // Тип поставки: download (скачать), box (коробка), key (ключ активации)
            $table->enum('delivery_type', ['download', 'box', 'key'])->default('key');

            // Основное изображение товара
            $table->string('main_image')->nullable();

            // Минимальная цена (для отображения "от X тг" в каталоге)
            // decimal(10, 2) — число до 10 цифр, 2 из которых после запятой
            // Например: 99999999.99
            $table->decimal('price_from', 10, 2)->nullable();

            // Статус товара
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');

            // Теги: хит продаж, новинка, акция
            $table->boolean('is_hit')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_sale')->default(false);

            // SEO-поля для продвижения в поисковиках
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // Счётчик просмотров
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

---

## 5.5 Миграция: product_licenses (Типы лицензий с ценами)

Один товар может иметь несколько вариантов лицензий с разными ценами:
- Лицензия на 1 ПК — 15 000 тг
- Лицензия на 5 ПК — 60 000 тг
- Подписка на 1 год — 8 000 тг

```bash
php artisan make:migration create_product_licenses_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // onDelete('cascade') — если товар удалён, все его лицензии тоже удаляются

            // Название варианта: "1 ПК", "5 ПК", "1 год / 1 ПК"
            $table->string('name');

            // Тип лицензии
            $table->enum('type', [
                'perpetual',    // Бессрочная
                'subscription', // Подписка
                'volume',       // Корпоративная (объёмная)
            ])->default('perpetual');

            // Количество устройств (1, 5, 10, unlimited)
            $table->string('devices')->nullable();

            // Срок действия: null = бессрочно, иначе количество месяцев
            $table->unsignedInteger('duration_months')->nullable();

            // Цена в тенге
            $table->decimal('price', 10, 2);

            // Старая цена (если есть скидка, показываем зачёркнутую)
            $table->decimal('old_price', 10, 2)->nullable();

            // Можно ли добавить в корзину или только "под запрос"
            $table->boolean('in_stock')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_licenses');
    }
};
```

---

## 5.6 Миграция: product_images (Изображения товара)

```bash
php artisan make:migration create_product_images_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Путь к файлу изображения: "products/office/screenshot1.jpg"
            $table->string('path');

            // Альтернативный текст для SEO и доступности
            $table->string('alt')->nullable();

            // Порядок отображения в галерее
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
```

---

## 5.7 Миграция: cart_items (Корзина)

```bash
php artisan make:migration create_cart_items_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // Чья корзина. nullable() — гость без авторизации тоже может иметь корзину
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Идентификатор сессии для гостей (без авторизации)
            $table->string('session_id')->nullable();

            // Какой товар
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Какая именно лицензия (тип/вариант товара)
            $table->foreignId('product_license_id')->constrained()->onDelete('cascade');

            // Количество (для ПО обычно 1, но для корпоративных заказов может быть больше)
            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            // Уникальная пара: один пользователь не может добавить одну и ту же лицензию дважды
            // unique(['user_id', 'product_license_id']) — составной уникальный индекс
            $table->unique(['user_id', 'product_license_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
```

---

## 5.8 Миграция: favorites (Избранное)

```bash
php artisan make:migration create_favorites_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            // Один пользователь не может добавить один товар в избранное дважды
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
```

---

## 5.9 Миграция: orders (Заказы)

```bash
php artisan make:migration create_orders_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Уникальный номер заказа: "ORD-2024-000001"
            $table->string('order_number')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Статус заказа
            $table->enum('status', [
                'pending',    // Ожидает оплаты
                'paid',       // Оплачен
                'processing', // В обработке
                'completed',  // Выполнен (ключ выдан)
                'cancelled',  // Отменён
                'refunded',   // Возврат
            ])->default('pending');

            // Контактные данные (копируем на момент заказа, т.к. пользователь может изменить их)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Финансы
            $table->decimal('subtotal', 10, 2);  // Сумма без скидок
            $table->decimal('discount', 10, 2)->default(0);  // Сумма скидки
            $table->decimal('total', 10, 2);     // Итоговая сумма

            // Комментарий покупателя к заказу
            $table->text('comment')->nullable();

            // Комментарий менеджера (внутренний)
            $table->text('admin_comment')->nullable();

            // Когда заказ был оплачен
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

---

## 5.10 Миграция: order_items (Позиции заказа)

```bash
php artisan make:migration create_order_items_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Сохраняем ссылку на товар, но nullable — товар может быть удалён позже
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Копируем данные на момент заказа! Цены и названия могут меняться,
            // но заказ должен хранить то, что было куплено
            $table->string('product_name');            // Копия названия товара
            $table->string('license_name');            // Копия названия лицензии
            $table->decimal('price', 10, 2);           // Цена на момент покупки
            $table->unsignedInteger('quantity')->default(1);

            // Лицензионный ключ — выдаётся после оплаты
            $table->text('license_key')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

---

## 5.11 Запуск всех миграций

```bash
php artisan migrate
```

**Что происходит:**
1. Laravel проверяет таблицу `migrations` в БД (создаёт её при первом запуске)
2. Определяет, какие миграции ещё не выполнялись
3. Выполняет метод `up()` каждой новой миграции по порядку (по timestamp в имени файла)
4. Записывает выполненные миграции в таблицу `migrations`

Если что-то пошло не так:

```bash
php artisan migrate:rollback   # Откатить последний пакет миграций
php artisan migrate:fresh      # Удалить все таблицы и применить заново (только для разработки!)
php artisan migrate:status     # Показать статус всех миграций
```

---

## 5.12 Модели Eloquent

**Модель** — это PHP-класс, который представляет одну таблицу в БД. Eloquent (ORM Laravel) позволяет работать с данными через объекты, а не через SQL.

### Модель Category

```bash
php artisan make:model Category
```

Открой `app/Models/Category.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    // $fillable — список полей, которые можно заполнять через массив
    // Это защита от "mass assignment" атаки — нельзя случайно перезаписать id или другие поля
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    // $casts — автоматическое приведение типов при чтении из БД
    // PostgreSQL хранит boolean как 't'/'f', cast преобразует в true/false PHP
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ===== СВЯЗИ (Relations) =====

    // Дочерние категории (подкатегории)
    // hasMany — "эта категория имеет много подкатегорий"
    // 'parent_id' — по какому полю связь
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Родительская категория
    // belongsTo — "эта категория принадлежит одной родительской"
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Товары этой категории
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

### Модель Vendor

```bash
php artisan make:model Vendor
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_name', 'logo',
        'description', 'website', 'country', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

### Модель Product

```bash
php artisan make:model Product
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_description', 'description',
        'category_id', 'vendor_id', 'version', 'language',
        'delivery_type', 'main_image', 'price_from', 'status',
        'is_hit', 'is_new', 'is_sale',
        'meta_title', 'meta_description', 'meta_keywords', 'views_count',
    ];

    protected $casts = [
        'price_from'  => 'decimal:2',
        'is_hit'      => 'boolean',
        'is_new'      => 'boolean',
        'is_sale'     => 'boolean',
        'views_count' => 'integer',
    ];

    // Связь: товар принадлежит одной категории
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Связь: товар принадлежит одному вендору
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Связь: у товара много лицензий (вариантов с ценами)
    public function licenses(): HasMany
    {
        return $this->hasMany(ProductLicense::class)->orderBy('sort_order');
    }

    // Связь: у товара много изображений
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    // Scope (прицельный запрос) — scope позволяет добавить условие WHERE к запросу
    // Использование: Product::active()->get()
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Product::hit()->get() — только хиты
    public function scopeHit($query)
    {
        return $query->where('is_hit', true);
    }
}
```

### Модели ProductLicense, ProductImage, CartItem, Favorite, Order, OrderItem

```bash
php artisan make:model ProductLicense
php artisan make:model ProductImage
php artisan make:model CartItem
php artisan make:model Favorite
php artisan make:model Order
php artisan make:model OrderItem
```

**app/Models/ProductLicense.php:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLicense extends Model
{
    protected $fillable = [
        'product_id', 'name', 'type', 'devices',
        'duration_months', 'price', 'old_price', 'in_stock', 'sort_order',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'old_price' => 'decimal:2',
        'in_stock'  => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

**app/Models/CartItem.php:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'product_id', 'product_license_id', 'quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(ProductLicense::class, 'product_license_id');
    }
}
```

**app/Models/Order.php:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'status',
        'customer_name', 'customer_email', 'customer_phone',
        'subtotal', 'discount', 'total', 'comment', 'admin_comment', 'paid_at',
    ];

    protected $casts = [
        'subtotal'  => 'decimal:2',
        'discount'  => 'decimal:2',
        'total'     => 'decimal:2',
        'paid_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

---

## 5.13 Seeders — заполнение базы тестовыми данными

**Seeder** — класс, который заполняет БД данными. Удобен для разработки и тестирования.

```bash
php artisan make:seeder CategorySeeder
php artisan make:seeder VendorSeeder
php artisan make:seeder ProductSeeder
```

**database/seeders/CategorySeeder.php:**

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Категории верхнего уровня
        $topCategories = [
            ['name' => 'Офисное ПО',          'sort_order' => 1],
            ['name' => 'Безопасность',         'sort_order' => 2],
            ['name' => 'Графика и дизайн',     'sort_order' => 3],
            ['name' => 'Инфраструктура',       'sort_order' => 4],
            ['name' => 'САПР',                 'sort_order' => 5],
            ['name' => 'Антивирусы',           'sort_order' => 6],
            ['name' => 'Мультимедиа',          'sort_order' => 7],
        ];

        foreach ($topCategories as $cat) {
            // Str::slug() — преобразует "Офисное ПО" → "ofisnoe-po"
            Category::create([
                'name'       => $cat['name'],
                'slug'       => Str::slug($cat['name']),
                'sort_order' => $cat['sort_order'],
                'is_active'  => true,
            ]);
        }

        // Подкатегории для "Офисное ПО" (parent_id = 1)
        $officeId = Category::where('slug', Str::slug('Офисное ПО'))->value('id');
        $subOffice = ['Microsoft Office', 'LibreOffice', 'Р7-Офис', 'МойОфис'];
        foreach ($subOffice as $i => $name) {
            Category::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'parent_id' => $officeId,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
```

**database/seeders/DatabaseSeeder.php** (главный seeder — вызывает остальные):

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём администратора
        User::create([
            'name'     => 'Администратор',
            'email'    => 'admin@asoft.kz',
            // Hash::make() — хешируем пароль. Никогда не храним пароли в открытом виде!
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Вызываем остальные seeders по порядку (важен порядок из-за внешних ключей)
        $this->call([
            CategorySeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
```

Запусти seeders:

```bash
php artisan db:seed
```

Или вместе с миграциями (сбросить всё и заполнить заново):

```bash
php artisan migrate:fresh --seed
```

---
