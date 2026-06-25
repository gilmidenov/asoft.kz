# Журнал изменений

## 2026-06-25 — Визуальные исправления, полноценный просмотр элементов разделов

### Изменения

#### 1. Исправлен flyout-каталог (был обрезан overflow)

**`resources/js/components/layout/AppHeader.vue`**

- Добавлен `w-full` к каждому `<div class="relative">` обёртке категории — `left-full` теперь позиционирует подменю точно по правому краю дропдауна.
- Убран `max-h-96 overflow-y-auto` с основного контейнера дропдауна — `overflow` CSS обрезал absolutely-positioned flyout и создавал скроллбар.

#### 2. Все элементы мини-каталога кликабельны + два поля текста

**`database/migrations/2026_06_25_000005_add_body_to_page_items.php`** — добавлено поле `body TEXT NULL` в `page_items`.

**`app/Models/PageItem.php`**, **`app/Http/Controllers/Api/PageController.php`** — `body` добавлен в `$fillable` и валидацию.

**`resources/js/pages/admin/PagesPage.vue`** — форма элемента разделена на два поля:
- **Краткое описание** (`content`) — виден на карточке как анонс
- **Описание** (`body`) — полный текст, показывается при открытии элемента

**`resources/js/pages/CompanyPage.vue`**:
- `@click="openDetail(item)"` на всех элементах (ранее только на тех, у кого есть файл)
- Модалка: изображение/PDF → иконка статьи (если нет файла) → краткое описание → полный текст
- Текстовые карточки без файла: иконка документа + укороченный заголовок вместо просто текста

#### 3. Улучшение отображения раздела-типа «Раздел»

**`resources/js/pages/CompanyPage.vue`** — тело раздела отображается в белой карточке (`bg-white rounded-2xl shadow-sm p-8`) с ограниченной шириной `max-w-3xl`, что улучшает читаемость длинного текста.

#### 4. Тестовый контент добавлен через API

- Баннер «test» удалён, добавлен нормальный баннер «Выгодные лицензии для бизнеса и госсектора»
- Раздел «О компании» переведён в тип «Раздел» и наполнен описанием компании
- Добавлен раздел «Акции и новости» (тип «Мини-каталог») с 3 элементами (текстовые карточки с кратким и полным описанием)

---

## 2026-06-25 — Flyout-каталог, два типа разделов, баннер без автопрокрутки

### Изменения

#### 1. Каталог — flyout-подменю подкатегорий

**`resources/js/components/layout/AppHeader.vue`**

Дропдаун каталога переработан: категории с дочерними показывают стрелку `>` справа; при наведении на такую категорию справа выезжает подменю с подкатегориями. Категории без детей выглядят как раньше. Технически: `hoveredCat = ref(null)` отслеживает hover; каждая строка категории обёрнута в `<div class="relative">` с `@mouseenter/@mouseleave`; подменю — `absolute left-full top-0`.

#### 2. Два типа разделов компании — «Мини-каталог» и «Раздел»

**Новая миграция** `2026_06_25_000004_add_type_body_cover_to_pages.php`:
- Добавлены поля `type ENUM('catalog','section') DEFAULT 'catalog'`, `body TEXT`, `cover_image VARCHAR(500)` в таблицу `pages`.

**`app/Models/Page.php`**:
- `$fillable` дополнен `type`, `body`, `cover_image`.
- Добавлен Eloquent Accessor `coverImage()` — возвращает полный Storage URL.

**`app/Http/Controllers/Api/PageController.php`**:
- `storePage`/`updatePage` принимают `type`, `body`.
- Новый метод `uploadCover(Request, int)` — загружает обложку в `page-covers/`, удаляет старую при замене.

**`routes/api.php`**:
- Добавлен маршрут `POST /admin/pages/{id}/cover`.

**`resources/js/pages/admin/PagesPage.vue`**:
- Форма раздела дополнена радио-кнопками «Мини-каталог» / «Раздел».
- Для типа «Раздел» показываются textarea для тела текста и загрузчик обложки.
- Для типа «Мини-каталог» — поведение прежнее; кнопка «Контент» скрыта для разделов-типа «Раздел».
- После создания «Мини-каталога» — автоматический переход в управление элементами; «Раздел» просто закрывает форму.

**`resources/js/pages/CompanyPage.vue`**:
- `v-if="page.type === 'section'"` → отображает `cover_image` + `body` как текст (`whitespace-pre-wrap`).
- `v-else` → прежняя сетка элементов mini-catalog.

#### 3. Баннер — только ручное переключение

**`resources/js/components/ui/BannerSlider.vue`**:
- Удалены `timer`, `startTimer`, `stopTimer`, `onUnmounted`.
- Убраны `@mouseenter/@mouseleave` с `<section>`.
- Баннер переключается только при клике на стрелки или точки навигации.

---

## 2026-06-25 — Исправления: карусель разделов, слайдер баннеров, ограничение длины

### Проблемы

1. **В форме создания раздела не было ограничения на длину названия** — длинное название ломало вёрстку навигационной полосы.

2. **Навигация разделов компании скроллилась горизонтально** (нативный скролл) — выглядело некрасиво.

3. **Баг слайдера**: при добавлении 1 баннера кнопки переключения не появлялись (fallback был выключен при наличии баннеров); кроме того, `<transition-group>` + `v-show` некорректно анимировал переходы.

---

### Решения

#### 1. Ограничение длины названия раздела

**`app/Http/Controllers/Api/PageController.php`:**

Валидация изменена с `max:255` на `max:40` в методах `storePage` и `updatePage`.

**`resources/js/pages/admin/PagesPage.vue`:**

В форме добавлен атрибут `maxlength="40"` и счётчик символов, который подсвечивается оранжевым цветом при превышении 35 символов:
```html
<span :class="pageForm.title.length > 35 ? 'text-accent' : 'text-gray-400'">
    {{ pageForm.title.length }}/40
</span>
```

#### 2. Карусель вместо прокрутки

**`resources/js/components/layout/AppHeader.vue`:**

Полностью переработана третья полоса шапки:

- Обёртка `overflow-x-auto` + CSS `scrollbar-width: none` — нативный скроллбар скрыт, JS-прокрутка работает
- Добавлены стрелки `«` / `»` — появляются динамически через `v-show="showPrev/showNext"`
- `updateArrows()` — пересчитывает видимость стрелок по `el.scrollLeft / scrollWidth / clientWidth`
- `scrollNav(±220)` — вызывает `el.scrollBy({ behavior: 'smooth' })`
- `watch(companyPages, ...)` — после загрузки разделов проверяет нужна ли стрелка вперёд
- Навигация перенесена ВНУТРЬ `<header class="sticky">` — теперь вся шапка (все 3 полосы) прилипает к верху

#### 3. Исправление слайдера баннеров

**`resources/js/components/ui/BannerSlider.vue`:**

**Проблема 1 — fallback исчезал:**
```js
// БЫЛО (неверно): fallback показывался только при 0 баннеров
const slides = computed(() => banners.value.length ? banners.value : [fallback])

// СТАЛО: fallback всегда первый слайд
const slides = computed(() => [fallback, ...banners.value])
```

Теперь: 0 баннеров → [fallback] (без стрелок); 1+ баннер → [fallback, b1, ...] (стрелки появляются).

**Проблема 2 — некорректная анимация:**
```vue
<!-- БЫЛО (неверно): transition-group предназначен для элементов входящих/выходящих DOM -->
<transition-group name="slide-fade" tag="div">
    <div v-show="idx === current" ...>   <!-- v-show меняет display, не DOM -->
</transition-group>

<!-- СТАЛО: CSS cross-fade — все слайды в DOM, видимость через opacity -->
<div v-for="(slide, idx) in slides" :key="idx"
     :class="idx === current
         ? 'opacity-100 z-10'
         : 'opacity-0 z-0 pointer-events-none'"
     class="absolute inset-0 transition-opacity duration-700">
```

`pointer-events-none` на невидимых слайдах предотвращает случайные клики насквозь.

---

### Файлы, затронутые исправлениями

| Файл | Что изменено |
|------|-------------|
| `app/Http/Controllers/Api/PageController.php` | `max:255` → `max:40` в `storePage` и `updatePage` |
| `resources/js/pages/admin/PagesPage.vue` | `maxlength="40"` + счётчик символов в форме раздела |
| `resources/js/components/layout/AppHeader.vue` | Карусель со стрелками; nav перенесён внутрь `<header>` (sticky) |
| `resources/js/components/ui/BannerSlider.vue` | slides всегда включает fallback; CSS cross-fade вместо transition-group |

---

## 2026-06-25 — Баннер-слайдер, разделы компании в шапке, полная документация

### Задачи

1. В шапку добавлены вкладки разделов компании (О компании, Решения, Разработка, Проекты, Новости, Нам доверяют, Карьера, Сертификаты, Реквизиты).
2. Статичный hero заменён на рекламный баннер-слайдер (несколько баннеров, меняются каждые 5 секунд или по клику).
3. Обновлена документация `docs/CODE.md` — описан весь текущий код.

---

### Новые файлы

#### Backend

| Файл | Назначение |
|------|------------|
| `database/migrations/2026_06_25_000001_create_banners_table.php` | Таблица баннеров (title, subtitle, button_text, button_url, image, sort_order, is_active) |
| `database/migrations/2026_06_25_000002_create_pages_table.php` | Таблица разделов компании (title, slug, description, sort_order, is_active) |
| `database/migrations/2026_06_25_000003_create_page_items_table.php` | Таблица элементов разделов (page_id, title, content, file_path, file_type, sort_order, is_active) |
| `app/Models/Banner.php` | Eloquent-модель баннера с accessor `image()` → полный URL |
| `app/Models/Page.php` | Eloquent-модель раздела компании; связи `items()` (только активные) и `allItems()` (все) |
| `app/Models/PageItem.php` | Eloquent-модель элемента раздела с accessor `filePath()` → полный URL |
| `app/Http/Controllers/Api/BannerController.php` | CRUD баннеров + загрузка изображения |
| `app/Http/Controllers/Api/PageController.php` | CRUD разделов и их элементов + загрузка файлов (изображение/PDF) |
| `database/seeders/PageSeeder.php` | Сидирует 9 разделов компании через `firstOrCreate` (идемпотентно) |

#### Frontend

| Файл | Назначение |
|------|------------|
| `resources/js/components/ui/BannerSlider.vue` | Слайдер: автопрокрутка (5 сек), стрелки, точки-индикаторы, пауза на hover, фолбэк если баннеров нет |
| `resources/js/pages/CompanyPage.vue` | Страница раздела компании: заголовок + мини-каталог элементов + лайтбокс для изображений/PDF |
| `resources/js/pages/admin/BannersPage.vue` | Admin: CRUD баннеров с загрузкой изображений |
| `resources/js/pages/admin/PagesPage.vue` | Admin: управление разделами + их элементами (два режима в одном компоненте) |

---

### Изменённые файлы

| Файл | Что изменено |
|------|-------------|
| `routes/api.php` | Добавлены маршруты для BannerController и PageController (публичные + admin) |
| `database/seeders/DatabaseSeeder.php` | Добавлен `PageSeeder::class` в список вызовов |
| `resources/js/pages/HomePage.vue` | Секция `<section class="...hero...">` заменена на `<BannerSlider />` |
| `resources/js/components/layout/AppHeader.vue` | Добавлена третья полоса `bg-slate-700` с навигацией по разделам компании; добавлена загрузка `companyPages` через `GET /api/pages` |
| `resources/js/router/index.js` | Добавлены маршруты `/company/:slug`, `/admin/banners`, `/admin/pages`; импорты `CompanyPage`, `AdminBanners`, `AdminPages` |
| `resources/js/pages/admin/AdminLayout.vue` | В sidebar добавлена секция «Контент» с ссылками на Баннеры и Разделы |

---

### Как работает баннер-слайдер

1. При монтировании `HomePage.vue` рендерит `<BannerSlider />`.
2. `BannerSlider.vue` делает `GET /api/banners` — получает активные баннеры.
3. Если баннеров нет — отображается фолбэк с оригинальным текстом.
4. Автопрокрутка: `setInterval(next, 5000)`. Таймер останавливается при `@mouseenter` и возобновляется при `@mouseleave`.
5. Переход между слайдами: `<transition-group name="slide-fade">` с CSS `opacity`.
6. Кнопка CTA: `<component :is="RouterLink|a">` — динамически выбирает тег по типу URL.

---

### Как работает навигация по разделам

1. `AppHeader.vue` при монтировании запрашивает `GET /api/pages`.
2. Разделы отрисовываются как `RouterLink` в третьей полосе шапки.
3. Клик по разделу открывает `/company/:slug`.
4. `CompanyPage.vue` загружает `GET /api/pages/:slug` — раздел + элементы.
5. Элементы отображаются сеткой карточек (изображения, PDF, статьи).
6. Клик по карточке с файлом открывает лайтбокс.

---

### Как добавить баннер (admin)

1. Войти как admin → `/admin/banners`.
2. Нажать «+ Добавить баннер».
3. Заполнить заголовок (обязательно), подзаголовок, текст и ссылку кнопки.
4. Загрузить изображение (JPG/PNG/WebP, до 8 МБ; рекомендуется 1920×600 px).
5. Установить порядок сортировки и нажать «Сохранить».
6. Баннер сразу отображается на главной.

### Как добавить контент в раздел (admin)

1. Войти как admin → `/admin/pages`.
2. Найти нужный раздел → нажать «Контент».
3. Нажать «+ Добавить элемент».
4. Заполнить название и текст.
5. Загрузить файл: изображение (JPG/PNG/WebP) или PDF (до 20 МБ). Тип определяется автоматически.
6. Нажать «Сохранить». Элемент появится на странице `/company/:slug`.

---

## 2026-06-23 — Исправление категорий/вендоров в меню и фильтрация по вендору

### Проблемы

1. **Новые категории и вендоры из админ-панели не появлялись на публичном сайте** (меню «Каталог», главная страница, список категорий) до перезагрузки страницы.

2. **Нажатие на вендора на странице `/vendors` не фильтровало товары** в каталоге — открывался каталог, но все товары отображались без фильтра по вендору.

---

### Причины

**Проблема 1 — кэш в Pinia-сторе**

`resources/js/stores/catalog.js` кэшировал категории и вендоров с охранником:

```js
if (categories.value.length) return   // ← однажды загрузив, не обновлял
```

Когда администратор создавал/удалял категорию в `/admin/categories`, страница перечитывала данные через собственный `load()`, но глобальный стор `catalogStore` оставался со старыми данными. Меню и главная страница используют `catalogStore.categories` — поэтому они не обновлялись.

**Проблема 2 — отсутствие поля `vendor` в фильтрах каталога**

`resources/js/pages/CatalogPage.vue` инициализировал `filters` без поля `vendor`:

```js
const filters = ref({ search, sort, price_from, price_to, is_hit, is_sale, page })
// vendor отсутствовал
```

При переходе с `/vendors` на `/catalog?vendor=microsoft` поле `vendor` не попадало в запрос к API — фильтрация не работала.

---

### Решения

#### 1. `resources/js/stores/catalog.js`

Добавлены методы `refreshCategories()` и `refreshVendors()`, которые принудительно перезапрашивают данные (без охранника):

```js
async function refreshCategories() {
    const { data } = await axios.get('/categories')
    categories.value = data
}

async function refreshVendors() {
    const { data } = await axios.get('/vendors')
    vendors.value = data
}
```

Оба метода экспортируются из стора.

#### 2. `resources/js/pages/admin/CategoriesPage.vue`

- Импортирован `useCatalogStore`
- После успешного `save()` (создание/редактирование) вызывается `catalogStore.refreshCategories()`
- После `remove()` (удаление) вызывается `catalogStore.refreshCategories()`

Результат: новая категория мгновенно появляется в меню «Каталог» и на главной странице без перезагрузки страницы.

#### 3. `resources/js/pages/admin/VendorsPage.vue`

- Импортирован `useCatalogStore`
- После успешного `save()` вызывается `catalogStore.refreshVendors()`
- После `remove()` вызывается `catalogStore.refreshVendors()`

Результат: новый вендор мгновенно появляется на странице `/vendors` и в фильтрах.

#### 4. `resources/js/pages/CatalogPage.vue`

Три изменения:

**а) Инициализация фильтра `vendor` из URL:**

```js
const filters = ref({
    // ... прочие поля
    vendor: route.query.vendor || '',   // ← добавлено
    // ...
})
```

**б) Передача `vendor` в API-запрос:**

```js
const params = {
    ...filters.value,
    vendor: filters.value.vendor || undefined,   // ← добавлено
    // ...
}
```

**в) Корректная запись `vendor` в URL (пустое значение → не попадает в строку запроса):**

```js
router.replace({
    query: {
        ...newFilters,
        vendor: newFilters.vendor || undefined,   // ← добавлено
    }
})
```

**г) Watcher для синхронизации фильтра при навигации внутри уже открытой страницы каталога:**

```js
watch(() => route.query.vendor, (newVendor) => {
    if (filters.value.vendor !== (newVendor || '')) {
        filters.value.vendor = newVendor || ''
    }
})
```

---

### Тестирование

1. Зайти в `/admin/categories` → создать новую категорию → перейти на главную (`/`) — новая категория появляется в сетке и в меню «Каталог» без перезагрузки страницы.
2. Зайти в `/admin/vendors` → создать нового вендора → перейти на `/vendors` — новый вендор появляется в списке.
3. На странице `/vendors` кликнуть по вендору — открывается `/catalog?vendor=<slug>` с товарами только этого вендора.

---

## 2026-06-23 — Страница вендора с товарами + подкатегории в сайдбаре каталога

### Проблемы

1. **Нажатие на вендора** сразу открывало каталог без какой-либо информации о вендоре — описание, сайт, логотип были нигде не видны.

2. **В каталоге при выборе категории** в сайдбаре не было фильтра по подкатегориям — пользователь не знал, какие подкатегории существуют у выбранной категории.

---

### Решения

#### 1. `resources/js/pages/VendorPage.vue` (новый файл)

Страница `/vendors/:slug` — карточка вендора:
- Логотип (или инициал, если логотип не задан)
- Название, описание, ссылка на сайт
- Счётчик товаров
- Сетка всех товаров вендора (через `GET /api/products?vendor=:slug`)
- Breadcrumb: Главная / Вендоры / Название вендора

#### 2. `resources/js/pages/VendorsPage.vue`

Ссылки с карточек вендоров изменены с `{ name: 'catalog', query: { vendor } }` на `{ name: 'vendor', params: { slug } }`. Карточки получили иконку-инициал и отображают описание вендора.

#### 3. `resources/js/router/index.js`

Добавлен маршрут `{ path: '/vendors/:slug', name: 'vendor', component: VendorPage }`.

#### 4. `resources/js/pages/CatalogPage.vue`

- Импортирован `useCatalogStore` (данные уже загружены хедером — без дополнительных запросов)
- Computed `currentCategory` — находит текущую категорию в сторе (родительская или дочерняя)
- Computed `subcategories` — возвращает детей родительской категории; если текущая категория дочерняя — возвращает братьев (siblings) для отображения в сайдбаре
- Computed `parentCategory` — родитель текущей категории (для breadcrumb и заголовка секции)
- Computed `categoryLabel` — реальное имя категории вместо prettified slug
- Sidebar: секция подкатегорий появляется над фильтрами, когда у категории есть дети/братья; активная подкатегория подсвечивается синим; ссылка «Все [родитель]» ведёт обратно на родительскую категорию
- Breadcrumb: показывает правильную иерархию Главная / Родитель / Подкатегория (было: slug через дефисы)

---

### Файлы, затронутые изменениями

| Файл | Что изменено |
|------|-------------|
| `resources/js/stores/catalog.js` | Добавлены `refreshCategories()` и `refreshVendors()` |
| `resources/js/pages/admin/CategoriesPage.vue` | Вызов `refreshCategories()` после CRUD-операций |
| `resources/js/pages/admin/VendorsPage.vue` | Вызов `refreshVendors()` после CRUD-операций |
| `resources/js/pages/CatalogPage.vue` | Поддержка фильтра `vendor` в URL, фильтрах и API-запросе |
| `public/build/assets/*` | Пересобранные бандлы (Vite build) |

---

### Файлы, затронутые изменениями (коммит 2)

| Файл | Что изменено |
|------|-------------|
| `resources/js/pages/VendorPage.vue` | Новая страница карточки вендора |
| `resources/js/pages/VendorsPage.vue` | Ссылки → `/vendors/:slug`, добавлены иконки и описание |
| `resources/js/router/index.js` | Маршрут `/vendors/:slug` → `VendorPage` |
| `resources/js/pages/CatalogPage.vue` | Sidebar подкатегорий, реальный breadcrumb, categoryLabel |
| `public/build/assets/*` | Пересобранные бандлы (Vite build) |

---

## 2026-06-23 — Подкатегории в форме товара, dropdown подкатегорий, загрузка изображений категорий/вендоров

### Проблемы

1. **Форма создания/редактирования товара** в админке показывала только корневые категории, не позволяя выбрать подкатегорию.

2. **Фильтр подкатегорий в сайдбаре каталога** отображался как список ссылок — неэстетично и занимал много места.

3. **Поле «Изображение»** у категорий и **«Логотип»** у вендоров принимали URL строкой, которые нигде не отображались (нет поддержки внешних ссылок в VendorPage).

---

### Решения

#### 1. `resources/js/pages/admin/ProductsPage.vue`

Выпадающий список категорий теперь включает подкатегории с отступом:

```html
<template v-for="c in categories" :key="c.id">
    <option :value="c.id">{{ c.name }}</option>
    <option v-for="child in (c.children || [])" :key="child.id" :value="child.id">
        &nbsp;&nbsp;&nbsp;└ {{ child.name }}
    </option>
</template>
```

Родительские категории выбираемы, подкатегории показаны с отступом и символом `└`. Оба уровня опциональны (поле не обязательное).

#### 2. `resources/js/pages/CatalogPage.vue`

Список ссылок подкатегорий заменён на `<select>`:

```html
<select :value="categorySlug" @change="(e) => router.push({ name: 'category', params: { slug: e.target.value } })">
    <option :value="parentCategory ? parentCategory.slug : categorySlug">Все …</option>
    <option v-for="sub in subcategories" :key="sub.id" :value="sub.slug">{{ sub.name }}</option>
</select>
```

При смене опции роутер переходит на выбранную подкатегорию. Текущая подкатегория автоматически выделена через `:value="categorySlug"`.

#### 3. Загрузка изображений категорий и вендоров

**Backend:**

- `app/Models/Category.php`: добавлен accessor `image()` — преобразует относительный путь в полный URL через `Storage::disk('public')->url($value)`, URL-ссылки остаются как есть
- `app/Models/Vendor.php`: добавлен accessor `logo()` — аналогично
- `app/Http/Controllers/Api/CategoryController.php`: добавлен метод `uploadImage()` — принимает файл, сохраняет в `storage/app/public/categories/`, удаляет старый файл (если был), возвращает обновлённую категорию с полным URL
- `app/Http/Controllers/Api/VendorController.php`: добавлен метод `uploadImage()` — аналогично, сохраняет в `storage/app/public/vendors/`
- `routes/api.php`: добавлены маршруты `POST /admin/categories/{id}/image` и `POST /admin/vendors/{id}/image`

**Frontend:**

- `resources/js/pages/admin/CategoriesPage.vue`: поле URL заменено зоной загрузки файла (аналогично ProductsPage); после сохранения/создания, если выбран файл — POST multipart на `/admin/categories/{id}/image`; в таблице добавлена колонка с превью изображения категории
- `resources/js/pages/admin/VendorsPage.vue`: поле URL заменено зоной загрузки логотипа; после сохранения POST на `/admin/vendors/{id}/image`; в таблице добавлена колонка с логотипом/инициалом

---

### Файлы, затронутые изменениями (коммит 3)

| Файл | Что изменено |
|------|-------------|
| `app/Models/Category.php` | Accessor `image()` → полный URL |
| `app/Models/Vendor.php` | Accessor `logo()` → полный URL |
| `app/Http/Controllers/Api/CategoryController.php` | Метод `uploadImage()` |
| `app/Http/Controllers/Api/VendorController.php` | Метод `uploadImage()` |
| `routes/api.php` | Маршруты `POST .../image` для категорий и вендоров |
| `resources/js/pages/admin/ProductsPage.vue` | Подкатегории в select категорий |
| `resources/js/pages/admin/CategoriesPage.vue` | Загрузка файла вместо URL-поля |
| `resources/js/pages/admin/VendorsPage.vue` | Загрузка файла вместо URL-поля |
| `resources/js/pages/CatalogPage.vue` | Dropdown подкатегорий вместо списка ссылок |
