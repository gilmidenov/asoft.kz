# Журнал изменений

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
