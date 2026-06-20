# Часть 9: Vue Router и Pinia {#часть-9}

## 9.1 Vue Router — маршрутизация

**Vue Router** — официальная библиотека маршрутизации Vue. Она связывает URL с компонентами: при переходе на `/catalog` показывается компонент `CatalogPage.vue` — без перезагрузки браузера.

Создай файл **resources/js/router/index.js:**

```javascript
import { createRouter, createWebHistory } from 'vue-router'

// Импорт страниц — компонентов верхнего уровня
// () => import(...) — "ленивая" загрузка: компонент загружается только при первом переходе
// Это ускоряет начальную загрузку сайта
const HomePage       = () => import('@/pages/HomePage.vue')
const CatalogPage    = () => import('@/pages/CatalogPage.vue')
const ProductPage    = () => import('@/pages/ProductPage.vue')
const CartPage       = () => import('@/pages/CartPage.vue')
const CheckoutPage   = () => import('@/pages/CheckoutPage.vue')
const LoginPage      = () => import('@/pages/LoginPage.vue')
const RegisterPage   = () => import('@/pages/RegisterPage.vue')
const AccountPage    = () => import('@/pages/AccountPage.vue')
const FavoritesPage  = () => import('@/pages/FavoritesPage.vue')
const VendorsPage    = () => import('@/pages/VendorsPage.vue')
const NotFoundPage   = () => import('@/pages/NotFoundPage.vue')

// Массив маршрутов — каждый объект описывает один маршрут
const routes = [
    {
        path: '/',          // URL
        name: 'home',       // Имя маршрута (для навигации: router.push({ name: 'home' }))
        component: HomePage, // Какой компонент показать
    },
    {
        path: '/catalog',
        name: 'catalog',
        component: CatalogPage,
    },
    {
        // :slug — динамический сегмент
        // При URL /catalog/ofisnoye-po  →  $route.params.slug = 'ofisnoye-po'
        path: '/catalog/:slug',
        name: 'category',
        component: CatalogPage,
    },
    {
        path: '/product/:slug',
        name: 'product',
        component: ProductPage,
    },
    {
        path: '/cart',
        name: 'cart',
        component: CartPage,
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: CheckoutPage,
        // meta — произвольные данные маршрута
        // requiresAuth: true — этот маршрут требует авторизации
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        // guest: true — этот маршрут только для неавторизованных
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterPage,
        meta: { guest: true },
    },
    {
        path: '/account',
        name: 'account',
        component: AccountPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/favorites',
        name: 'favorites',
        component: FavoritesPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/vendors',
        name: 'vendors',
        component: VendorsPage,
    },
    {
        // 404 — все остальные маршруты
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: NotFoundPage,
    },
]

const router = createRouter({
    // createWebHistory() — использует HTML5 History API для "красивых" URL
    // Вместо /#!/catalog будет просто /catalog
    history: createWebHistory(),
    routes,

    // scrollBehavior — поведение прокрутки при навигации
    scrollBehavior(to, from, savedPosition) {
        // Если пользователь нажал "Назад" — вернуть в сохранённую позицию
        if (savedPosition) {
            return savedPosition
        }
        // Иначе — прокрутить вверх
        return { top: 0 }
    },
})

// Глобальный Navigation Guard — выполняется перед каждым переходом
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('auth_token')

    // Если маршрут требует авторизации и пользователь не авторизован
    if (to.meta.requiresAuth && !isAuthenticated) {
        // Перенаправляем на страницу входа
        // query: { redirect: to.fullPath } — сохраняем куда хотели перейти
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    // Если маршрут только для гостей и пользователь уже авторизован
    if (to.meta.guest && isAuthenticated) {
        next({ name: 'account' })
        return
    }

    // В остальных случаях — разрешаем переход
    next()
})

export default router
```

---

## 9.2 Pinia — управление состоянием

**Pinia** — хранилище глобального состояния. Данные, которые нужны в нескольких компонентах (пользователь, корзина), хранятся здесь.

**Почему не просто `ref` в компоненте?**
- Данные корзины нужны в Header (счётчик), CartPage и ProductPage
- Если хранить в каждом компоненте отдельно — данные рассинхронизируются
- Pinia — единый источник правды

### Store: useAuthStore

**resources/js/stores/auth.js:**

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// defineStore('auth', ...) — создаём store с именем 'auth'
// Имя используется для отладки в Vue DevTools
export const useAuthStore = defineStore('auth', () => {

    // ===== STATE (состояние) =====
    // ref() — реактивная переменная. При её изменении компоненты обновятся.
    const user  = ref(null)   // Данные текущего пользователя
    const token = ref(localStorage.getItem('auth_token')) // Токен из localStorage

    // ===== GETTERS (вычисляемые значения) =====
    // computed() — пересчитывается только когда изменяется зависимость (token или user)
    const isAuthenticated = computed(() => !!token.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    // ===== ACTIONS (методы) =====

    async function login(email, password) {
        const response = await axios.post('/auth/login', { email, password })

        // Сохраняем токен
        token.value = response.data.token
        user.value  = response.data.user

        // Сохраняем в localStorage (переживёт перезагрузку страницы)
        localStorage.setItem('auth_token', token.value)
    }

    async function register(name, email, password, passwordConfirmation) {
        const response = await axios.post('/auth/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        })

        token.value = response.data.token
        user.value  = response.data.user
        localStorage.setItem('auth_token', token.value)
    }

    async function logout() {
        try {
            await axios.post('/auth/logout')
        } finally {
            // Очищаем состояние даже если запрос упал
            token.value = null
            user.value  = null
            localStorage.removeItem('auth_token')
        }
    }

    // Загрузить данные пользователя (вызывается при старте приложения)
    async function fetchUser() {
        if (!token.value) return
        try {
            const response = await axios.get('/auth/me')
            user.value = response.data
        } catch {
            // Токен невалиден — разлогиниваем
            token.value = null
            localStorage.removeItem('auth_token')
        }
    }

    // Возвращаем всё, что должно быть доступно извне
    return { user, token, isAuthenticated, isAdmin, login, register, logout, fetchUser }
})
```

### Store: useCartStore

**resources/js/stores/cart.js:**

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useCartStore = defineStore('cart', () => {

    // ===== STATE =====
    const items   = ref([])   // Массив позиций корзины
    const loading = ref(false)

    // ===== GETTERS =====

    // Общее количество товаров в корзине
    const count = computed(() =>
        items.value.reduce((sum, item) => sum + item.quantity, 0)
    )

    // Итоговая сумма
    const total = computed(() =>
        items.value.reduce((sum, item) => sum + item.license.price * item.quantity, 0)
    )

    // ===== ACTIONS =====

    async function fetchCart() {
        loading.value = true
        try {
            const response = await axios.get('/cart')
            items.value = response.data.items
        } finally {
            loading.value = false
        }
    }

    async function addItem(licenseId, quantity = 1) {
        const response = await axios.post('/cart', {
            product_license_id: licenseId,
            quantity,
        })
        // Обновляем корзину после добавления
        await fetchCart()
        return response.data
    }

    async function updateItem(itemId, quantity) {
        await axios.patch(`/cart/${itemId}`, { quantity })
        // Обновляем локально без нового запроса
        const item = items.value.find(i => i.id === itemId)
        if (item) item.quantity = quantity
    }

    async function removeItem(itemId) {
        await axios.delete(`/cart/${itemId}`)
        items.value = items.value.filter(i => i.id !== itemId)
    }

    async function clearCart() {
        await axios.delete('/cart')
        items.value = []
    }

    return { items, loading, count, total, fetchCart, addItem, updateItem, removeItem, clearCart }
})
```

### Store: useCatalogStore

**resources/js/stores/catalog.js:**

```javascript
import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useCatalogStore = defineStore('catalog', () => {

    const categories = ref([])
    const vendors    = ref([])

    async function fetchCategories() {
        if (categories.value.length) return // Кэшируем — не загружаем повторно
        const response = await axios.get('/categories')
        categories.value = response.data
    }

    async function fetchVendors() {
        if (vendors.value.length) return
        const response = await axios.get('/vendors')
        vendors.value = response.data
    }

    return { categories, vendors, fetchCategories, fetchVendors }
})
```

---

## 9.3 Корневой компонент App.vue

**resources/js/App.vue:**

```vue
<script setup>
// <script setup> — синтаксис Composition API.
// Весь код здесь выполняется при создании компонента.
// Переменные и функции автоматически доступны в шаблоне.

import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'

const authStore = useAuthStore()
const cartStore = useCartStore()

// onMounted — колбэк, выполняется когда компонент встроен в DOM
onMounted(async () => {
    // При загрузке приложения восстанавливаем сессию
    await authStore.fetchUser()
    await cartStore.fetchCart()
})
</script>

<template>
    <!-- RouterView — placeholder: здесь рендерится компонент текущего маршрута -->
    <div class="min-h-screen flex flex-col">
        <AppHeader />

        <main class="flex-1">
            <!-- Transition — анимация при смене страниц -->
            <RouterView v-slot="{ Component }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" />
                </Transition>
            </RouterView>
        </main>

        <AppFooter />
    </div>
</template>

<style>
/* Анимация переходов между страницами */
.page-enter-active,
.page-leave-active {
    transition: opacity 0.15s ease;
}
.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>
```

---

# Часть 10: Компоненты и страницы {#часть-10}

## Структура папок frontend

```
resources/js/
├── app.js                          # Точка входа
├── App.vue                         # Корневой компонент
├── router/
│   └── index.js                    # Маршруты
├── stores/
│   ├── auth.js                     # Pinia store: пользователь
│   ├── cart.js                     # Pinia store: корзина
│   └── catalog.js                  # Pinia store: каталог
├── pages/                          # Страницы (один компонент = одна страница)
│   ├── HomePage.vue
│   ├── CatalogPage.vue
│   ├── ProductPage.vue
│   ├── CartPage.vue
│   ├── CheckoutPage.vue
│   ├── LoginPage.vue
│   ├── RegisterPage.vue
│   ├── AccountPage.vue
│   ├── FavoritesPage.vue
│   ├── VendorsPage.vue
│   └── NotFoundPage.vue
└── components/                     # Переиспользуемые компоненты
    ├── layout/
    │   ├── AppHeader.vue           # Шапка сайта
    │   ├── AppFooter.vue           # Подвал сайта
    │   └── AppSidebar.vue         # Боковое меню категорий
    ├── catalog/
    │   ├── ProductCard.vue         # Карточка товара
    │   ├── ProductGrid.vue         # Сетка карточек
    │   ├── ProductFilters.vue      # Фильтры каталога
    │   └── CategoryTree.vue        # Дерево категорий
    ├── product/
    │   ├── LicenseSelector.vue     # Выбор лицензии
    │   └── ProductGallery.vue      # Галерея изображений
    ├── cart/
    │   └── CartItem.vue            # Позиция в корзине
    └── ui/
        ├── BaseButton.vue          # Кнопка
        ├── BaseInput.vue           # Поле ввода
        ├── BasePagination.vue      # Пагинация
        ├── BaseSpinner.vue         # Индикатор загрузки
        └── BaseBreadcrumbs.vue     # Хлебные крошки
```

---

## 10.1 Компонент AppHeader

**resources/js/components/layout/AppHeader.vue:**

```vue
<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCatalogStore } from '@/stores/catalog'

const authStore   = useAuthStore()
const cartStore   = useCartStore()
const catalogStore = useCatalogStore()

// ref() для реактивного состояния внутри компонента
const searchQuery       = ref('')
const mobileMenuOpen    = ref(false)
const catalogMenuOpen   = ref(false)

// Загружаем категории при монтировании компонента
import { onMounted } from 'vue'
onMounted(() => catalogStore.fetchCategories())

// Метод поиска
function handleSearch() {
    if (searchQuery.value.trim()) {
        // Навигация с параметром запроса: /catalog?search=офис
        router.push({ name: 'catalog', query: { search: searchQuery.value } })
    }
}

import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
    <!-- Шапка состоит из двух полос: верхней (контакты) и нижней (навигация) -->
    <header class="sticky top-0 z-50 shadow-md">

        <!-- Верхняя полоса: тёмная, с контактами и действиями -->
        <div class="bg-header text-white">
            <div class="container mx-auto px-4 py-2 flex items-center justify-between text-sm">

                <!-- Логотип -->
                <RouterLink to="/" class="flex items-center gap-2">
                    <!-- SVG логотип: "A" в зелёном кружке -->
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center font-bold text-white text-lg">
                        A
                    </div>
                    <div>
                        <span class="font-bold text-white text-lg tracking-tight">Atlas</span>
                        <span class="text-primary font-bold text-lg"> Software</span>
                    </div>
                </RouterLink>

                <!-- Центр: телефон -->
                <a href="tel:+77001234567" class="text-gray-300 hover:text-white transition-colors">
                    +7 (700) 123-45-67
                </a>

                <!-- Правая часть: действия пользователя -->
                <div class="flex items-center gap-4">

                    <!-- Корзина -->
                    <RouterLink to="/cart" class="flex items-center gap-1 text-gray-300 hover:text-white transition-colors relative">
                        <!-- Иконка корзины (SVG) -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Корзина
                        <!-- Счётчик товаров — показывается если count > 0 -->
                        <span v-if="cartStore.count > 0"
                            class="absolute -top-2 -right-3 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                            {{ cartStore.count }}
                        </span>
                    </RouterLink>

                    <!-- Избранное -->
                    <RouterLink to="/favorites" class="text-gray-300 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </RouterLink>

                    <!-- Авторизация -->
                    <template v-if="authStore.isAuthenticated">
                        <RouterLink to="/account" class="text-gray-300 hover:text-white transition-colors text-sm">
                            {{ authStore.user?.name }}
                        </RouterLink>
                        <button @click="authStore.logout()" class="text-gray-400 hover:text-white text-sm transition-colors">
                            Выйти
                        </button>
                    </template>
                    <template v-else>
                        <RouterLink to="/login" class="text-gray-300 hover:text-white transition-colors">
                            Войти
                        </RouterLink>
                    </template>
                </div>
            </div>
        </div>

        <!-- Нижняя полоса: белая, с поиском и навигацией -->
        <div class="bg-white border-b border-gray-200">
            <div class="container mx-auto px-4 py-3 flex items-center gap-6">

                <!-- Кнопка "Каталог" с выпадающим меню -->
                <div class="relative">
                    <button
                        @click="catalogMenuOpen = !catalogMenuOpen"
                        class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-700 transition-colors"
                    >
                        <!-- Иконка бургер-меню -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Каталог
                    </button>

                    <!-- Выпадающее меню каталога -->
                    <!-- v-if — элемент рендерится только когда catalogMenuOpen = true -->
                    <div v-if="catalogMenuOpen"
                        class="absolute top-full left-0 mt-1 w-64 bg-white shadow-xl rounded-lg border border-gray-100 py-2 z-50">
                        <RouterLink
                            v-for="cat in catalogStore.categories"
                            :key="cat.id"
                            :to="{ name: 'category', params: { slug: cat.slug } }"
                            @click="catalogMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-dark hover:text-primary transition-colors"
                        >
                            {{ cat.name }}
                        </RouterLink>
                    </div>
                </div>

                <!-- Строка поиска -->
                <!-- @submit.prevent — перехватываем сабмит формы, предотвращаем перезагрузку -->
                <form @submit.prevent="handleSearch" class="flex-1 max-w-xl">
                    <div class="flex">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Поиск программ..."
                            class="flex-1 border border-gray-300 border-r-0 rounded-l-lg px-4 py-2 text-sm focus:outline-none focus:border-primary"
                        />
                        <button type="submit"
                            class="bg-primary text-white px-4 py-2 rounded-r-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Быстрые ссылки -->
                <nav class="hidden lg:flex items-center gap-5 text-sm font-medium">
                    <RouterLink to="/vendors" class="text-dark hover:text-primary transition-colors">Вендоры</RouterLink>
                    <RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }" class="text-dark hover:text-primary transition-colors">Хиты</RouterLink>
                    <RouterLink :to="{ name: 'catalog', query: { is_sale: 1 } }" class="text-accent hover:text-accent-dark transition-colors font-semibold">Акции</RouterLink>
                </nav>
            </div>
        </div>
    </header>

    <!-- Оверлей для закрытия меню при клике вне него -->
    <div v-if="catalogMenuOpen" @click="catalogMenuOpen = false" class="fixed inset-0 z-40" />
</template>
```

---

## 10.2 Компонент ProductCard

**resources/js/components/catalog/ProductCard.vue:**

```vue
<script setup>
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'
import { ref } from 'vue'

// defineProps — объявляем входные данные компонента (принимаются от родителя)
const props = defineProps({
    product: {
        type: Object,
        required: true,
    }
})

const cartStore = useCartStore()
const router    = useRouter()
const adding    = ref(false) // флаг: идёт ли добавление в корзину

async function addToCart() {
    // Берём первую (самую дешёвую) лицензию
    const license = props.product.licenses?.[0]
    if (!license) return

    adding.value = true
    try {
        await cartStore.addItem(license.id)
    } finally {
        adding.value = false
    }
}

// Форматирование цены: 15000 → "15 000 ₸"
function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', {
        style: 'currency',
        currency: 'KZT',
        minimumFractionDigits: 0,
    }).format(price)
}
</script>

<template>
    <!-- Карточка товара — кликабельна, ведёт на страницу товара -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col">

        <!-- Изображение товара -->
        <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="block">
            <div class="relative aspect-square p-4 flex items-center justify-center bg-gray-50 rounded-t-xl overflow-hidden">
                <!-- v-if / v-else — условный рендеринг -->
                <img
                    v-if="product.main_image"
                    :src="product.main_image"
                    :alt="product.name"
                    class="w-full h-full object-contain"
                />
                <!-- Заглушка, если нет изображения -->
                <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                    </svg>
                </div>

                <!-- Теги: Хит, Новинка, Акция -->
                <div class="absolute top-2 left-2 flex flex-col gap-1">
                    <span v-if="product.is_hit" class="bg-accent text-white text-xs font-bold px-2 py-0.5 rounded">
                        ХИТ
                    </span>
                    <span v-if="product.is_new" class="bg-primary text-white text-xs font-bold px-2 py-0.5 rounded">
                        НОВИНКА
                    </span>
                    <span v-if="product.is_sale" class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded">
                        АКЦИЯ
                    </span>
                </div>
            </div>
        </RouterLink>

        <!-- Информация о товаре -->
        <div class="p-4 flex flex-col flex-1">
            <!-- Вендор -->
            <span v-if="product.vendor" class="text-xs text-muted mb-1 uppercase tracking-wide">
                {{ product.vendor.short_name || product.vendor.name }}
            </span>

            <!-- Название товара -->
            <RouterLink
                :to="{ name: 'product', params: { slug: product.slug } }"
                class="text-dark font-medium text-sm hover:text-primary transition-colors leading-snug mb-2 line-clamp-2"
            >
                {{ product.name }}
            </RouterLink>

            <!-- Тип лицензии -->
            <span v-if="product.licenses?.length" class="text-xs text-muted mb-3">
                {{ product.licenses[0].name }}
            </span>

            <!-- Spacer — растягивает, чтобы цена была внизу -->
            <div class="flex-1" />

            <!-- Цена и кнопка -->
            <div class="flex items-center justify-between gap-2 mt-2">
                <div>
                    <!-- Если есть лицензии — показываем цену -->
                    <template v-if="product.price_from">
                        <span class="text-xs text-muted">от</span>
                        <span class="text-lg font-bold text-accent">
                            {{ formatPrice(product.price_from) }}
                        </span>
                    </template>
                    <span v-else class="text-sm text-muted">Под запрос</span>
                </div>

                <!-- Кнопка "В корзину" -->
                <button
                    @click.prevent="addToCart"
                    :disabled="adding || !product.licenses?.length"
                    class="bg-primary text-white text-sm px-3 py-1.5 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                >
                    <!-- v-if/v-else для состояния загрузки -->
                    <span v-if="adding">...</span>
                    <span v-else>В корзину</span>
                </button>
            </div>
        </div>
    </div>
</template>
```

---

## 10.3 Страница HomePage

**resources/js/pages/HomePage.vue:**

```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProductCard from '@/components/catalog/ProductCard.vue'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()
const hitProducts  = ref([])
const newProducts  = ref([])
const loading      = ref(false)

onMounted(async () => {
    loading.value = true
    try {
        await catalogStore.fetchCategories()
        // Параллельно загружаем хиты и новинки
        // Promise.all — выполняет запросы одновременно (быстрее чем по очереди)
        const [hitsResponse, newResponse] = await Promise.all([
            axios.get('/products', { params: { is_hit: 1, per_page: 8 } }),
            axios.get('/products', { params: { sort: 'new', per_page: 8 } }),
        ])
        hitProducts.value = hitsResponse.data.data
        newProducts.value = newResponse.data.data
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div>
        <!-- ===== HERO СЕКЦИЯ ===== -->
        <section class="bg-gradient-to-br from-header via-slate-800 to-primary-900 text-white py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-2xl">
                    <h1 class="text-4xl font-bold mb-4 leading-tight">
                        Лицензионное программное<br>
                        <span class="text-primary">обеспечение</span> для бизнеса
                    </h1>
                    <p class="text-gray-300 text-lg mb-8">
                        Официальные лицензии Microsoft, Kaspersky, Adobe и других ведущих вендоров.
                        Мгновенная доставка ключей активации.
                    </p>
                    <div class="flex gap-4">
                        <RouterLink to="/catalog"
                            class="bg-primary hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                            Перейти в каталог
                        </RouterLink>
                        <a href="tel:+77001234567"
                            class="border border-gray-500 hover:border-white text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                            Позвонить нам
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== КАТЕГОРИИ ===== -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-dark mb-6">Категории</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    <RouterLink
                        v-for="cat in catalogStore.categories"
                        :key="cat.id"
                        :to="{ name: 'category', params: { slug: cat.slug } }"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-primary hover:bg-primary-50 transition-all group text-center"
                    >
                        <!-- Иконка категории (квадрат с первой буквой) -->
                        <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center group-hover:bg-primary transition-colors">
                            <span class="text-primary group-hover:text-white font-bold text-lg">
                                {{ cat.name[0] }}
                            </span>
                        </div>
                        <span class="text-xs font-medium text-dark group-hover:text-primary leading-tight">
                            {{ cat.name }}
                        </span>
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- ===== ХИТЫ ПРОДАЖ ===== -->
        <section class="py-12 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-dark">
                        Хиты продаж
                        <span class="text-accent">🔥</span>
                    </h2>
                    <RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }"
                        class="text-primary hover:text-primary-700 text-sm font-medium">
                        Смотреть все →
                    </RouterLink>
                </div>

                <!-- Сетка карточек -->
                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Скелетон-заглушки при загрузке -->
                    <div v-for="i in 8" :key="i" class="bg-white rounded-xl h-72 animate-pulse" />
                </div>
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <!-- v-for — рендерим карточку для каждого товара -->
                    <!-- :product="p" — передаём объект товара в компонент через prop -->
                    <ProductCard v-for="p in hitProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- ===== НОВИНКИ ===== -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-dark">Новинки</h2>
                    <RouterLink :to="{ name: 'catalog', query: { sort: 'new' } }"
                        class="text-primary hover:text-primary-700 text-sm font-medium">
                        Смотреть все →
                    </RouterLink>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="p in newProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- ===== ПРЕИМУЩЕСТВА ===== -->
        <section class="py-12 bg-header text-white">
            <div class="container mx-auto px-4">
                <div class="grid md:grid-cols-4 gap-8">
                    <div v-for="benefit in [
                        { icon: '🔑', title: 'Мгновенная доставка', text: 'Ключи активации на email в течение часа' },
                        { icon: '✅', title: 'Официальные лицензии', text: 'Только оригинальное ПО от авторизованных дистрибьюторов' },
                        { icon: '📞', title: 'Техподдержка', text: 'Помогаем с установкой и активацией' },
                        { icon: '💰', title: 'Лучшие цены', text: 'Прямые поставки без посредников' },
                    ]" :key="benefit.title" class="text-center">
                        <div class="text-4xl mb-3">{{ benefit.icon }}</div>
                        <h3 class="font-semibold text-lg mb-2">{{ benefit.title }}</h3>
                        <p class="text-gray-400 text-sm">{{ benefit.text }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
```

---
