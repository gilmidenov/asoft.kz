# Руководство: создание разделов с подразделами

## Что мы построили и как это работает

Система «Разработка → Веб-разработка» — это пример паттерна **хаб + лендинг**:

```
/company/development       ← хаб-страница (показывает карточки подразделов)
/company/veb-razrabotka    ← подраздел с собственным дизайном + портфолио из БД
```

---

## Архитектура системы страниц

### Таблица `pages` (БД)

Каждая страница — запись в таблице `pages`:

| Поле | Тип | Назначение |
|------|-----|-----------|
| `title` | string | Название (до 30 символов) |
| `slug` | string | URL-идентификатор (`veb-razrabotka`) |
| `description` | text | Краткое описание (subtitle) |
| `type` | enum | `catalog` — сетка элементов; `section` — текст+обложка |
| `body` | text | Основной текст (для type=section) |
| `cover_image` | string | Путь к обложке (для type=section) |
| `sort_order` | integer | Порядок в навигации |
| `is_active` | boolean | Страница активна (доступна по URL) |
| `show_in_nav` | boolean | Показывать в карусели шапки и подвале |

### Таблица `page_items` (БД)

Каждый элемент портфолио/контента — запись в `page_items`:

| Поле | Тип | Назначение |
|------|-----|-----------|
| `page_id` | FK | Ссылка на страницу-родителя |
| `title` | string | Название элемента (проекта) |
| `content` | text | Краткое описание (анонс) |
| `body` | text | Полное описание (в модалке) |
| `file_type` | enum | `image` / `pdf` / `text` |
| `file_path` | string | Путь к файлу |
| `sort_order` | integer | Порядок отображения |
| `is_active` | boolean | Показывать ли элемент |

### Маршруты API

```
GET  /api/pages                     → список (только is_active=true AND show_in_nav=true)
GET  /api/pages/{slug}              → одна страница + её items (только is_active=true)

POST   /api/admin/pages             → создать страницу
PUT    /api/admin/pages/{id}        → обновить страницу
DELETE /api/admin/pages/{id}        → удалить страницу

POST   /api/admin/pages/{id}/items  → создать элемент (портфолио)
PUT    /api/admin/items/{id}        → обновить элемент
DELETE /api/admin/items/{id}        → удалить элемент + файл
POST   /api/admin/items/{id}/file   → загрузить изображение/PDF к элементу
```

### Маршруты Vue Router

```js
// router/index.js — порядок важен: специфичный ПЕРЕД общим
{ path: '/company/veb-razrabotka', name: 'web-dev',      component: WebDevPage }
{ path: '/company/:slug',          name: 'company-page', component: CompanyPage }
```

---

## Как создать новый подраздел (пошаговая инструкция)

Допустим, нужно добавить «Мобильная разработка» внутри «Разработка».

### Шаг 1 — Создать Vue-компонент

Создать файл `resources/js/pages/MobileDevPage.vue`.  
Структура та же, что у `WebDevPage.vue`:

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'

const portfolio = ref([])
const loading   = ref(true)
const detail    = ref(null)

onMounted(async () => {
    try {
        const { data } = await axios.get('/pages/mobilnaya-razrabotka')
        portfolio.value = data.items || []
    } catch { /* ничего */ }
    finally { loading.value = false }
})
</script>

<template>
    <!-- Hero, услуги, процесс, портфолио — своя вёрстка -->
</template>
```

**Ключевое:** в `axios.get('/pages/ВАШ-SLUG')` подставьте slug вашей страницы из БД.

### Шаг 2 — Зарегистрировать маршрут

Открыть `resources/js/router/index.js`:

```js
// 1. Импорт (ленивая загрузка — загружается только при переходе)
const MobileDevPage = () => import('@/pages/MobileDevPage.vue')

// 2. Маршрут — ОБЯЗАТЕЛЬНО перед '/company/:slug'
{ path: '/company/mobilnaya-razrabotka', name: 'mobile-dev', component: MobileDevPage },
{ path: '/company/:slug',                name: 'company-page', component: CompanyPage },
```

> ⚠️ Если поставить специфичный маршрут ПОСЛЕ `/:slug`, Vue Router никогда до него не дойдёт — `:slug` поглотит всё.

### Шаг 3 — Добавить страницу в базу данных

Способ 1 — через Docker (одноразово):
```bash
docker exec asoft_app php artisan tinker --execute="
App\Models\Page::create([
    'title'       => 'Мобильная разработка',
    'slug'        => 'mobilnaya-razrabotka',
    'description' => 'iOS и Android приложения под ключ',
    'type'        => 'catalog',
    'sort_order'  => 32,
    'is_active'   => true,
    'show_in_nav' => false,   // скрыть из карусели шапки
]);
echo 'done';
"
```

Способ 2 — через Seeder (рекомендуется для воспроизводимости):
```php
// database/seeders/PagesSeeder.php
Page::firstOrCreate(['slug' => 'mobilnaya-razrabotka'], [
    'title'       => 'Мобильная разработка',
    'slug'        => 'mobilnaya-razrabotka',
    'description' => 'iOS и Android приложения под ключ',
    'type'        => 'catalog',
    'sort_order'  => 32,
    'is_active'   => true,
    'show_in_nav' => false,
]);
```

> **Почему `show_in_nav: false`?** Подразделы не должны появляться в карусели шапки — туда идут только верхние разделы (`development`, `solutions` и т.д.). Подраздел доступен по прямому URL и через хаб-страницу родителя.

### Шаг 4 — Добавить карточку в хаб «Разработка»

Открыть `resources/js/pages/CompanyPage.vue`, найти блок с классом `slug === 'development'` и добавить новую карточку рядом с «Веб-разработкой»:

```html
<!-- Мобильная разработка -->
<RouterLink to="/company/mobilnaya-razrabotka"
    class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm
           hover:shadow-md hover:border-primary/30 transition-all group flex flex-col">
    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-4
                group-hover:bg-primary/20 transition-colors">
        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
    </div>
    <h3 class="text-base font-bold text-dark mb-2 group-hover:text-primary transition-colors">
        Мобильная разработка
    </h3>
    <p class="text-muted text-sm leading-relaxed flex-1">
        Нативные iOS и Android приложения, кроссплатформенная разработка
    </p>
    <div class="mt-4 flex items-center gap-1.5 text-primary text-sm font-medium">
        <span>Подробнее</span>
        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
    </div>
</RouterLink>
```

И убрать или заменить карточку «Мобильная разработка (Скоро)».

### Шаг 5 — Собрать фронтенд

```bash
npm run build
```

---

## Как добавлять проекты в портфолио через Админку

### Где это находится

**Админка → Страницы → кликнуть на «Веб-разработка»**

URL: `https://asoft.kz/admin` → раздел «Страницы»

### Пошаговые действия

1. Зайти в `/admin` → «Страницы»
2. Найти строку **«Веб-разработка»** → нажать **«Контент»**
3. В открывшемся экране нажать **«+ Добавить элемент»**
4. Заполнить форму:

| Поле | Что вводить |
|------|------------|
| **Название** | Имя проекта: «Сайт для ТОО Ромашка» |
| **Краткое описание** | 1–2 строки: «Корпоративный сайт с CMS, каталогом и формой заявок» |
| **Подробный текст** | Детали: технологии, срок, особенности (отображается в модалке) |
| **Файл** | Скриншот сайта (JPG/PNG/WebP, желательно 1280×720) |
| **Порядок** | Число — чем меньше, тем выше в списке |
| **Активен** | Галочка = показывать на сайте |

5. Нажать «Сохранить»
6. В следующем шаге загрузить скриншот кнопкой «Загрузить файл»

### Как выглядит результат

После сохранения элемент сразу появляется на странице `/company/veb-razrabotka` в разделе «Портфолио» — карточка с превью скриншота, названием и описанием. По клику открывается модальное окно с полным изображением и подробным текстом.

### Советы по скриншотам

- **Размер:** 1280×720 пикселей (соотношение 16:9) — идеально вписывается в карточку
- **Формат:** WebP даёт лучшее качество при меньшем размере
- **Инструменты:** можно сделать скриншот в браузере через DevTools → Capture screenshot

---

## Управление видимостью в навигации (поле show_in_nav)

### Из Админки

1. Зайти в `/admin` → «Страницы»
2. Нажать карандаш (редактировать) рядом с нужной страницей
3. В форме найти чекбокс **«В навигации»**:
   - ✅ **Включено** — страница появляется в карусели шапки и подвала
   - ☐ **Выключено** — страница скрыта из навигации, но доступна по прямому URL

### Правило

| Тип страницы | `show_in_nav` |
|-------------|--------------|
| Верхний раздел (О компании, Разработка, Проекты...) | `true` |
| Подраздел (Веб-разработка, Мобильная разработка...) | `false` |

---

## Структура файлов, задействованных в этой функциональности

```
resources/js/
├── pages/
│   ├── CompanyPage.vue          ← универсальная страница + хаб для slug='development'
│   ├── WebDevPage.vue           ← страница «Веб-разработка» (кастомный дизайн)
│   └── admin/
│       └── PagesPage.vue        ← управление страницами и элементами в админке
├── router/
│   └── index.js                 ← маршруты (порядок важен!)
└── components/layout/
    ├── AppHeader.vue            ← карусель разделов (использует GET /api/pages)
    └── AppFooter.vue            ← ссылки в подвале (использует GET /api/pages)

app/
├── Models/
│   ├── Page.php                 ← модель страницы
│   └── PageItem.php             ← модель элемента (портфолио)
└── Http/Controllers/Api/
    └── PageController.php       ← API: public index/show + admin CRUD

database/migrations/
└── ..._add_show_in_nav_to_pages_table.php   ← добавление колонки show_in_nav
```

---

## Чек-лист при добавлении нового подраздела

- [ ] Создан `resources/js/pages/ИмяPage.vue`
- [ ] В компоненте `axios.get('/pages/ВАШ-SLUG')` для загрузки портфолио
- [ ] В `router/index.js`: специфичный маршрут добавлен **перед** `'/company/:slug'`
- [ ] Запись в БД создана через tinker или Seeder (`show_in_nav: false`)
- [ ] В `CompanyPage.vue` в блоке `slug === 'development'` добавлена карточка RouterLink
- [ ] Запущен `npm run build`
- [ ] Проверено: `/company/development` → карточка есть, ссылка работает
- [ ] Проверено: карточка **не появилась** в карусели шапки (show_in_nav=false)
- [ ] Проверено: `/company/ВАШ-SLUG` открывает новый лендинг
