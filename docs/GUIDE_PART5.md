# Часть 10 (продолжение): Страница каталога и товара

## 10.4 Страница CatalogPage

**resources/js/pages/CatalogPage.vue:**

```vue
<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import ProductCard from '@/components/catalog/ProductCard.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

const route  = useRoute()   // Текущий маршрут (params, query)
const router = useRouter()  // Для навигации

// Состояние страницы
const products   = ref([])
const pagination = ref(null) // { current_page, last_page, total }
const loading    = ref(false)
const categories = ref([])

// Фильтры — связаны с URL через watch
const filters = ref({
    search:    route.query.search    || '',
    sort:      route.query.sort      || 'default',
    price_from: route.query.price_from || '',
    price_to:  route.query.price_to  || '',
    is_hit:    route.query.is_hit    === '1',
    is_sale:   route.query.is_sale   === '1',
    page:      route.query.page      ? Number(route.query.page) : 1,
})

// Слаг категории из URL (если переходим на /catalog/ofisnoye-po)
const categorySlug = computed(() => route.params.slug || null)

async function loadProducts() {
    loading.value = true
    try {
        const params = {
            ...filters.value,
            is_hit:  filters.value.is_hit  ? 1 : undefined,
            is_sale: filters.value.is_sale ? 1 : undefined,
            // Если есть слаг категории — фильтруем по нему
            category: categorySlug.value || undefined,
        }
        const response = await axios.get('/products', { params })
        // Laravel paginate() возвращает: { data: [...], current_page, last_page, total, ... }
        products.value   = response.data.data
        pagination.value = response.data
    } finally {
        loading.value = false
    }
}

// watch() — реагирует на изменение реактивных переменных
// При изменении filters — обновляем URL и перезагружаем товары
watch(filters, (newFilters) => {
    // Синхронизируем фильтры с URL
    router.replace({
        query: {
            ...newFilters,
            is_hit:  newFilters.is_hit  ? '1' : undefined,
            is_sale: newFilters.is_sale ? '1' : undefined,
        }
    })
    loadProducts()
}, { deep: true }) // deep: true — следим за вложенными полями объекта

// При изменении категории в URL — перезагружаем
watch(() => route.params.slug, () => loadProducts())

onMounted(() => loadProducts())

// Методы
function changePage(page) {
    filters.value.page = page
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function resetFilters() {
    filters.value = { search: '', sort: 'default', price_from: '', price_to: '', is_hit: false, is_sale: false, page: 1 }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Хлебные крошки -->
        <nav class="text-sm text-muted mb-6">
            <RouterLink to="/" class="hover:text-primary">Главная</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-dark">Каталог</span>
            <template v-if="categorySlug">
                <span class="mx-2">/</span>
                <span class="text-dark capitalize">{{ categorySlug.replace(/-/g, ' ') }}</span>
            </template>
        </nav>

        <div class="flex gap-6">
            <!-- ===== БОКОВАЯ ПАНЕЛЬ ФИЛЬТРОВ ===== -->
            <aside class="hidden lg:block w-56 flex-shrink-0">

                <!-- Поиск -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Поиск</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Название программы..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                    />
                </div>

                <!-- Сортировка -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Сортировка</label>
                    <!-- v-model на select — двустороннее связывание: изменение select меняет filters.sort и наоборот -->
                    <select v-model="filters.sort"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                        <option value="default">По популярности</option>
                        <option value="price_asc">Сначала дешевле</option>
                        <option value="price_desc">Сначала дороже</option>
                        <option value="name_asc">По названию А-Я</option>
                        <option value="new">Сначала новые</option>
                    </select>
                </div>

                <!-- Цена -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Цена (₸)</label>
                    <div class="flex gap-2">
                        <input v-model="filters.price_from" type="number" placeholder="от"
                            class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <input v-model="filters.price_to" type="number" placeholder="до"
                            class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>
                </div>

                <!-- Теги -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Фильтры</label>
                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                        <!-- v-model на checkbox связывает с boolean-значением -->
                        <input type="checkbox" v-model="filters.is_hit" class="text-primary rounded" />
                        <span class="text-sm">Хиты продаж 🔥</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="filters.is_sale" class="text-primary rounded" />
                        <span class="text-sm">Акции</span>
                    </label>
                </div>

                <button @click="resetFilters"
                    class="w-full border border-gray-300 text-muted text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
                    Сбросить фильтры
                </button>
            </aside>

            <!-- ===== ОСНОВНОЙ КОНТЕНТ ===== -->
            <div class="flex-1">
                <!-- Заголовок и счётчик -->
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold text-dark">
                        {{ categorySlug ? 'Категория' : 'Все программы' }}
                    </h1>
                    <span v-if="pagination" class="text-sm text-muted">
                        Найдено: {{ pagination.total }} товаров
                    </span>
                </div>

                <!-- Состояние загрузки: скелетоны -->
                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="i in 12" :key="i" class="bg-white rounded-xl h-72 animate-pulse border border-gray-100" />
                </div>

                <!-- Нет результатов -->
                <div v-else-if="products.length === 0" class="text-center py-16 text-muted">
                    <div class="text-6xl mb-4">🔍</div>
                    <p class="text-lg font-medium">Ничего не найдено</p>
                    <p class="text-sm mt-1">Попробуйте изменить параметры поиска</p>
                    <button @click="resetFilters" class="mt-4 text-primary hover:underline text-sm">
                        Сбросить фильтры
                    </button>
                </div>

                <!-- Сетка товаров -->
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>

                <!-- Пагинация -->
                <BasePagination
                    v-if="pagination && pagination.last_page > 1"
                    :current-page="pagination.current_page"
                    :last-page="pagination.last_page"
                    class="mt-8"
                    @change="changePage"
                />
            </div>
        </div>
    </div>
</template>
```

---

## 10.5 Страница ProductPage

**resources/js/pages/ProductPage.vue:**

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const route     = useRoute()
const cartStore = useCartStore()

const product         = ref(null)
const loading         = ref(true)
const selectedLicense = ref(null) // Выбранный вариант лицензии
const adding          = ref(false)
const addedToCart     = ref(false) // Флаг "добавлено!" для UX

onMounted(async () => {
    try {
        const response = await axios.get(`/products/${route.params.slug}`)
        product.value = response.data
        // По умолчанию выбираем первую лицензию
        selectedLicense.value = product.value.licenses?.[0] || null
    } finally {
        loading.value = false
    }
})

async function addToCart() {
    if (!selectedLicense.value) return
    adding.value = true
    try {
        await cartStore.addItem(selectedLicense.value.id)
        addedToCart.value = true
        // Через 2 секунды сбрасываем флаг
        setTimeout(() => addedToCart.value = false, 2000)
    } finally {
        adding.value = false
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', { style: 'currency', currency: 'KZT', minimumFractionDigits: 0 }).format(price)
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Загрузка -->
        <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-48 mb-8" />
            <div class="grid md:grid-cols-2 gap-8">
                <div class="aspect-square bg-gray-200 rounded-xl" />
                <div class="space-y-4">
                    <div class="h-8 bg-gray-200 rounded w-3/4" />
                    <div class="h-4 bg-gray-200 rounded" />
                    <div class="h-4 bg-gray-200 rounded w-2/3" />
                </div>
            </div>
        </div>

        <template v-else-if="product">
            <!-- Хлебные крошки -->
            <nav class="text-sm text-muted mb-6">
                <RouterLink to="/" class="hover:text-primary">Главная</RouterLink>
                <span class="mx-2">/</span>
                <RouterLink to="/catalog" class="hover:text-primary">Каталог</RouterLink>
                <template v-if="product.category">
                    <span class="mx-2">/</span>
                    <RouterLink :to="{ name: 'category', params: { slug: product.category.slug } }" class="hover:text-primary">
                        {{ product.category.name }}
                    </RouterLink>
                </template>
                <span class="mx-2">/</span>
                <span class="text-dark">{{ product.name }}</span>
            </nav>

            <!-- Основной блок -->
            <div class="grid md:grid-cols-2 gap-8 mb-10">
                <!-- Изображение -->
                <div class="bg-white rounded-xl border border-gray-100 p-8 flex items-center justify-center aspect-square">
                    <img v-if="product.main_image" :src="product.main_image" :alt="product.name" class="max-h-64 object-contain" />
                    <div v-else class="w-32 h-32 bg-gray-100 rounded-xl" />
                </div>

                <!-- Информация -->
                <div>
                    <!-- Вендор -->
                    <div v-if="product.vendor" class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-semibold text-primary">{{ product.vendor.name }}</span>
                    </div>

                    <h1 class="text-2xl font-bold text-dark mb-2">{{ product.name }}</h1>

                    <!-- Теги -->
                    <div class="flex gap-2 mb-4">
                        <span v-if="product.is_hit" class="bg-accent/10 text-accent text-xs font-bold px-2 py-1 rounded">ХИТ</span>
                        <span v-if="product.is_new" class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded">НОВИНКА</span>
                        <span v-if="product.language" class="bg-gray-100 text-muted text-xs px-2 py-1 rounded">{{ product.language }}</span>
                        <span v-if="product.version" class="bg-gray-100 text-muted text-xs px-2 py-1 rounded">v{{ product.version }}</span>
                    </div>

                    <p v-if="product.short_description" class="text-muted mb-6">{{ product.short_description }}</p>

                    <!-- ===== ВЫБОР ЛИЦЕНЗИИ ===== -->
                    <div v-if="product.licenses?.length" class="mb-6">
                        <h3 class="font-semibold text-dark mb-3">Выберите лицензию:</h3>
                        <div class="space-y-2">
                            <label
                                v-for="license in product.licenses"
                                :key="license.id"
                                class="flex items-center justify-between p-3 border-2 rounded-xl cursor-pointer transition-all"
                                :class="selectedLicense?.id === license.id
                                    ? 'border-primary bg-primary/5'  /* выбранная лицензия */
                                    : 'border-gray-200 hover:border-gray-300'"
                            >
                                <div class="flex items-center gap-3">
                                    <!-- radio-кнопка привязана к selectedLicense -->
                                    <input
                                        type="radio"
                                        :value="license"
                                        v-model="selectedLicense"
                                        class="text-primary"
                                    />
                                    <div>
                                        <div class="font-medium text-dark text-sm">{{ license.name }}</div>
                                        <div v-if="license.duration_months" class="text-xs text-muted">
                                            {{ license.duration_months }} мес.
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <!-- Зачёркнутая старая цена -->
                                    <div v-if="license.old_price" class="text-xs text-muted line-through">
                                        {{ formatPrice(license.old_price) }}
                                    </div>
                                    <div class="font-bold text-accent">{{ formatPrice(license.price) }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Кнопка "В корзину" -->
                    <div class="flex gap-3">
                        <button
                            @click="addToCart"
                            :disabled="adding || !selectedLicense || !selectedLicense.in_stock"
                            class="flex-1 py-3 px-6 rounded-xl font-semibold text-white transition-all"
                            :class="addedToCart
                                ? 'bg-green-500'
                                : 'bg-primary hover:bg-primary-700 disabled:opacity-50'"
                        >
                            <span v-if="addedToCart">✓ Добавлено в корзину!</span>
                            <span v-else-if="adding">Добавляем...</span>
                            <span v-else-if="!selectedLicense?.in_stock">Под запрос</span>
                            <span v-else>В корзину</span>
                        </button>
                    </div>

                    <!-- Доставка -->
                    <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-muted flex items-center gap-2">
                        🔑 Ключ активации на email в течение 1 часа
                    </div>
                </div>
            </div>

            <!-- Описание товара -->
            <div v-if="product.description" class="bg-white rounded-xl border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-dark mb-4">Описание</h2>
                <!-- v-html — рендерит HTML-строку. Используй только с доверенным контентом! -->
                <div class="prose max-w-none text-muted" v-html="product.description" />
            </div>
        </template>

        <!-- 404 -->
        <div v-else class="text-center py-16">
            <div class="text-6xl mb-4">😕</div>
            <h2 class="text-xl font-bold text-dark mb-2">Товар не найден</h2>
            <RouterLink to="/catalog" class="text-primary hover:underline">Вернуться в каталог</RouterLink>
        </div>
    </div>
</template>
```

---

## 10.6 Страница CartPage

**resources/js/pages/CartPage.vue:**

```vue
<script setup>
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()
const router    = useRouter()

function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', { style: 'currency', currency: 'KZT', minimumFractionDigits: 0 }).format(price)
}

function goToCheckout() {
    if (!authStore.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: '/checkout' } })
        return
    }
    router.push({ name: 'checkout' })
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-dark mb-6">Корзина</h1>

        <!-- Пустая корзина -->
        <div v-if="cartStore.items.length === 0" class="text-center py-16">
            <div class="text-7xl mb-4">🛒</div>
            <h2 class="text-xl font-bold text-dark mb-2">Корзина пуста</h2>
            <p class="text-muted mb-6">Добавьте товары из каталога</p>
            <RouterLink to="/catalog"
                class="bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors">
                Перейти в каталог
            </RouterLink>
        </div>

        <div v-else class="grid lg:grid-cols-3 gap-6">
            <!-- ===== Список товаров ===== -->
            <div class="lg:col-span-2 space-y-4">
                <div
                    v-for="item in cartStore.items"
                    :key="item.id"
                    class="bg-white rounded-xl border border-gray-100 p-4 flex gap-4"
                >
                    <!-- Изображение -->
                    <div class="w-20 h-20 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <img v-if="item.product?.main_image" :src="item.product.main_image" :alt="item.product.name" class="w-16 h-16 object-contain" />
                        <span v-else class="text-3xl">📦</span>
                    </div>

                    <!-- Информация -->
                    <div class="flex-1">
                        <h3 class="font-medium text-dark">{{ item.product?.name }}</h3>
                        <p class="text-sm text-muted">{{ item.license?.name }}</p>
                        <p class="text-accent font-bold mt-1">{{ formatPrice(item.license?.price) }}</p>
                    </div>

                    <!-- Количество и удаление -->
                    <div class="flex flex-col items-end gap-2">
                        <button @click="cartStore.removeItem(item.id)" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Изменение количества -->
                        <div class="flex items-center gap-2">
                            <button @click="cartStore.updateItem(item.id, Math.max(1, item.quantity - 1))"
                                class="w-6 h-6 border rounded text-center hover:bg-gray-50">−</button>
                            <span class="text-sm font-medium w-6 text-center">{{ item.quantity }}</span>
                            <button @click="cartStore.updateItem(item.id, item.quantity + 1)"
                                class="w-6 h-6 border rounded text-center hover:bg-gray-50">+</button>
                        </div>
                    </div>
                </div>

                <!-- Очистить корзину -->
                <button @click="cartStore.clearCart()" class="text-sm text-red-500 hover:underline">
                    Очистить корзину
                </button>
            </div>

            <!-- ===== Итог заказа ===== -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-100 p-5 sticky top-24">
                    <h2 class="font-bold text-dark text-lg mb-4">Итог</h2>

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-muted">Товаров:</span>
                            <span>{{ cartStore.count }} шт.</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 mt-2">
                            <span class="font-semibold text-dark">Итого:</span>
                            <span class="font-bold text-xl text-accent">{{ formatPrice(cartStore.total) }}</span>
                        </div>
                    </div>

                    <button @click="goToCheckout"
                        class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors">
                        Оформить заказ
                    </button>

                    <p class="text-xs text-muted text-center mt-3">
                        🔒 Безопасная оплата. Ключи активации на email.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
```

---

## 10.7 Страница LoginPage и RegisterPage

**resources/js/pages/LoginPage.vue:**

```vue
<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router    = useRouter()
const route     = useRoute()

const form   = ref({ email: '', password: '' })
const errors = ref({})
const loading = ref(false)

async function submit() {
    errors.value = {}
    loading.value = true
    try {
        await authStore.login(form.value.email, form.value.password)
        // После логина — редирект на страницу, с которой пришли
        const redirect = route.query.redirect || '/'
        router.push(redirect)
    } catch (e) {
        // Если Laravel вернул 422 — показываем ошибки валидации
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        }
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 w-full max-w-md">

            <!-- Логотип -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-3xl">A</span>
                </div>
                <h1 class="text-2xl font-bold text-dark">Войти в аккаунт</h1>
                <p class="text-muted text-sm mt-1">Atlas Software</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-dark mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="your@email.com"
                        class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary transition-colors"
                        :class="errors.email ? 'border-red-400' : 'border-gray-300'"
                    />
                    <!-- Ошибки валидации -->
                    <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
                </div>

                <!-- Пароль -->
                <div>
                    <label class="block text-sm font-medium text-dark mb-1">Пароль</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="••••••••"
                        class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary transition-colors"
                        :class="errors.password ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="errors.password" class="text-red-500 text-xs mt-1">{{ errors.password[0] }}</p>
                </div>

                <!-- Глобальная ошибка (неверный email/пароль) -->
                <div v-if="errors.email && errors.email[0]?.includes('пароль')"
                    class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg">
                    {{ errors.email[0] }}
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors disabled:opacity-60">
                    {{ loading ? 'Входим...' : 'Войти' }}
                </button>
            </form>

            <p class="text-center text-sm text-muted mt-6">
                Нет аккаунта?
                <RouterLink to="/register" class="text-primary hover:underline font-medium">Зарегистрироваться</RouterLink>
            </p>
        </div>
    </div>
</template>
```

---

## 10.8 Компонент BasePagination

**resources/js/components/ui/BasePagination.vue:**

```vue
<script setup>
// defineEmits — объявляем события, которые компонент может вызывать
const emit = defineEmits(['change'])

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage:    { type: Number, required: true },
})

// Генерируем массив страниц для отображения
// Например: [1, '...', 4, 5, 6, '...', 20]
function getPages() {
    const pages = []
    const delta = 2 // Сколько страниц показывать вокруг текущей

    for (let i = 1; i <= props.lastPage; i++) {
        if (
            i === 1 ||
            i === props.lastPage ||
            (i >= props.currentPage - delta && i <= props.currentPage + delta)
        ) {
            pages.push(i)
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...')
        }
    }
    return pages
}
</script>

<template>
    <div class="flex items-center justify-center gap-1">
        <!-- Кнопка "Назад" -->
        <button
            @click="emit('change', currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-100 transition-colors"
        >
            ←
        </button>

        <!-- Номера страниц -->
        <template v-for="page in getPages()" :key="page">
            <!-- Разделитель "..." -->
            <span v-if="page === '...'" class="px-2 text-muted">…</span>
            <!-- Кнопка страницы -->
            <button v-else
                @click="emit('change', page)"
                class="w-9 h-9 rounded-lg text-sm font-medium transition-colors"
                :class="page === currentPage
                    ? 'bg-primary text-white'
                    : 'hover:bg-gray-100 text-dark'"
            >
                {{ page }}
            </button>
        </template>

        <!-- Кнопка "Вперёд" -->
        <button
            @click="emit('change', currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-100 transition-colors"
        >
            →
        </button>
    </div>
</template>
```

---

## 10.9 AppFooter

**resources/js/components/layout/AppFooter.vue:**

```vue
<template>
    <footer class="bg-header text-white mt-16">
        <div class="container mx-auto px-4 py-10">
            <div class="grid md:grid-cols-4 gap-8">

                <!-- Компания -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center font-bold">A</div>
                        <span class="font-bold">Atlas Software</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Интернет-магазин лицензионного программного обеспечения в Казахстане
                    </p>
                    <p class="text-gray-400 text-sm mt-3">+7 (700) 123-45-67</p>
                    <p class="text-gray-400 text-sm">info@asoft.kz</p>
                </div>

                <!-- Каталог -->
                <div>
                    <h4 class="font-semibold mb-4">Каталог</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><RouterLink to="/catalog" class="hover:text-white transition-colors">Все программы</RouterLink></li>
                        <li><RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }" class="hover:text-white transition-colors">Хиты продаж</RouterLink></li>
                        <li><RouterLink :to="{ name: 'catalog', query: { is_new: 1 } }" class="hover:text-white transition-colors">Новинки</RouterLink></li>
                        <li><RouterLink to="/vendors" class="hover:text-white transition-colors">Вендоры</RouterLink></li>
                    </ul>
                </div>

                <!-- Помощь -->
                <div>
                    <h4 class="font-semibold mb-4">Помощь</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Как активировать ключ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Способы оплаты</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Возврат и обмен</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Контакты</a></li>
                    </ul>
                </div>

                <!-- Контакты -->
                <div>
                    <h4 class="font-semibold mb-4">Адрес</h4>
                    <p class="text-gray-400 text-sm">Казахстан, Алматы</p>
                    <p class="text-gray-400 text-sm mt-4 font-medium">Время работы:</p>
                    <p class="text-gray-400 text-sm">Пн-Пт: 9:00 – 18:00</p>
                    <p class="text-gray-400 text-sm">Сб: 10:00 – 15:00</p>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-sm">© {{ new Date().getFullYear() }} Atlas Software. Все права защищены.</p>
                <p class="text-gray-600 text-xs">asoft.kz — официальные лицензии ПО в Казахстане</p>
            </div>
        </div>
    </footer>
</template>
```

---

# Часть 11: Запуск в режиме разработки {#часть-11}

## 11.1 Запуск двух серверов

Для разработки нужно запустить **два процесса одновременно** — в двух терминалах:

**Терминал 1 — Laravel:**
```bash
php artisan serve
# Сервер запустится на http://localhost:8000
```

**Терминал 2 — Vite (frontend):**
```bash
npm run dev
# Vite запустится на http://localhost:5173
# Открывай http://localhost:5173 в браузере
```

**Почему два сервера:**
- Laravel (порт 8000) — обрабатывает API-запросы (`/api/*`)
- Vite (порт 5173) — раздаёт HTML, CSS, JS с горячей перезагрузкой (HMR). При изменении `.vue` файла браузер обновляется мгновенно без перезагрузки

---

## 11.2 Полезные команды Artisan

```bash
# Создание файлов
php artisan make:model Product -m      # Модель + миграция одной командой
php artisan make:controller Api/ProductController  # Контроллер
php artisan make:seeder ProductSeeder  # Сидер

# База данных
php artisan migrate                    # Применить новые миграции
php artisan migrate:rollback           # Откатить последние миграции
php artisan migrate:fresh --seed       # Пересоздать все таблицы + заполнить данными

# Кэш (очистить при проблемах с конфигурацией)
php artisan config:clear               # Очистить кэш конфигурации
php artisan cache:clear                # Очистить кэш приложения
php artisan route:clear                # Очистить кэш маршрутов

# Просмотр маршрутов
php artisan route:list                 # Список всех маршрутов

# Тинкер — интерактивная консоль PHP с доступом к Laravel
php artisan tinker
# Примеры в tinker:
# > User::count()
# > Product::with('category')->first()
# > App\Models\Category::all()
```

---

# Часть 12: Тестирование {#часть-12}

## 12.1 PHPUnit — тестирование Laravel

Laravel использует **PHPUnit** — стандартный фреймворк тестирования PHP.

### Настройка тестовой базы данных

В `phpunit.xml` (уже есть в корне проекта) добавь/раскомментируй:

```xml
<php>
    <!-- Используем SQLite в памяти для тестов — быстро и изолированно -->
    <!-- Тесты не затрагивают твою рабочую базу данных! -->
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <!-- Отключаем кэш при тестировании -->
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
</php>
```

---

## 12.2 Тест аутентификации

```bash
php artisan make:test AuthTest
```

**tests/Feature/AuthTest.php:**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // RefreshDatabase — перед каждым тестом откатывает все изменения в БД
    // Это гарантирует изоляцию тестов
    use RefreshDatabase;

    /** @test */
    public function user_can_register(): void
    {
        // Отправляем POST-запрос к API
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Иван Иванов',
            'email'                 => 'ivan@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Проверяем HTTP-статус 201 Created
        $response->assertStatus(201);

        // Проверяем структуру JSON-ответа
        $response->assertJsonStructure([
            'user'  => ['id', 'name', 'email'],
            'token',
        ]);

        // Проверяем, что пользователь действительно создан в БД
        $this->assertDatabaseHas('users', ['email' => 'ivan@test.com']);
    }

    /** @test */
    public function user_cannot_register_with_duplicate_email(): void
    {
        // Создаём пользователя через фабрику
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Другой',
            'email'                 => 'existing@test.com', // Дублирующийся email
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 422 Unprocessable Entity — ошибка валидации
        $response->assertStatus(422);
        // Проверяем, что в ответе есть ошибка для поля email
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    /** @test */
    public function user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('correct')]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        // actingAs() — авторизуем пользователя в тесте без логина
        // 'sanctum' — драйвер аутентификации
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout');

        $response->assertOk();
    }
}
```

---

## 12.3 Тест каталога товаров

```bash
php artisan make:test ProductTest
```

**tests/Feature/ProductTest.php:**

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_get_products_list(): void
    {
        // Фабрика создаёт 5 товаров со статусом active
        Product::factory()->count(5)->create(['status' => 'active']);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        // Проверяем структуру пагинации Laravel
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'price_from']
            ],
            'current_page',
            'total',
            'last_page',
        ]);
    }

    /** @test */
    public function can_filter_products_by_category(): void
    {
        $category = Category::factory()->create(['slug' => 'ofisnoye-po']);
        $other    = Category::factory()->create(['slug' => 'other']);

        // Создаём 3 товара в нашей категории и 2 в другой
        Product::factory()->count(3)->create(['category_id' => $category->id, 'status' => 'active']);
        Product::factory()->count(2)->create(['category_id' => $other->id, 'status' => 'active']);

        $response = $this->getJson('/api/products?category=ofisnoye-po');

        $response->assertOk();
        // total должен быть 3 (только товары нашей категории)
        $this->assertEquals(3, $response->json('total'));
    }

    /** @test */
    public function can_get_single_product(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
            'slug'   => 'microsoft-office',
        ]);

        $response = $this->getJson('/api/products/microsoft-office');

        $response->assertOk();
        $response->assertJsonPath('name', $product->name);
    }

    /** @test */
    public function returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/not-exists');
        $response->assertNotFound();
    }
}
```

---

## 12.4 Создание Factory (фабрик)

Фабрики нужны для создания тестовых данных:

```bash
php artisan make:factory ProductFactory --model=Product
php artisan make:factory CategoryFactory --model=Category
```

**database/factories/ProductFactory.php:**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        // fake() — объект Faker, генерирует случайные данные
        $name = fake()->words(3, true);

        return [
            'name'              => ucfirst($name),
            'slug'              => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'short_description' => fake()->sentence(),
            'description'       => fake()->paragraphs(3, true),
            'status'            => fake()->randomElement(['active', 'active', 'active', 'inactive']),
            'price_from'        => fake()->randomFloat(0, 1000, 50000),
            'delivery_type'     => 'key',
            'is_hit'            => fake()->boolean(20),  // 20% chance
            'is_new'            => fake()->boolean(15),
            'is_sale'           => fake()->boolean(10),
            'views_count'       => fake()->numberBetween(0, 1000),
        ];
    }
}
```

**database/factories/CategoryFactory.php:**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);
        return [
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }
}
```

---

## 12.5 Запуск тестов

```bash
# Запустить все тесты
php artisan test

# Запустить только один файл
php artisan test tests/Feature/AuthTest.php

# Запустить конкретный тест
php artisan test --filter user_can_register

# Подробный вывод
php artisan test --verbose

# Тесты с покрытием кода (требует xdebug)
php artisan test --coverage
```

---

## 12.6 Тестирование Vue (Vitest)

**Vitest** — быстрый тест-раннер для Vite-проектов. Тестирует Vue-компоненты.

### Установка

```bash
npm install -D vitest @vue/test-utils jsdom
```

**vite.config.js** — добавь секцию `test`:

```javascript
export default defineConfig({
    // ... существующая конфигурация ...
    test: {
        // Среда выполнения: jsdom эмулирует браузер
        environment: 'jsdom',
        globals: true, // Глобально доступны describe, it, expect без импорта
    },
})
```

**package.json** — добавь скрипт:
```json
{
    "scripts": {
        "test:vue": "vitest"
    }
}
```

### Тест компонента ProductCard

**resources/js/tests/ProductCard.test.js:**

```javascript
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing' // Pinia для тестов

// Мокируем vue-router, чтобы не нужна была полная настройка
vi.mock('vue-router', () => ({
    RouterLink: { template: '<a><slot /></a>' },
    useRouter: () => ({ push: vi.fn() }),
}))

import ProductCard from '@/components/catalog/ProductCard.vue'

// Тестовые данные товара
const mockProduct = {
    id:          1,
    name:        'Microsoft Office 365',
    slug:        'microsoft-office-365',
    price_from:  '15000.00',
    main_image:  null,
    is_hit:      true,
    is_new:      false,
    is_sale:     false,
    vendor:      { name: 'Microsoft', short_name: 'Microsoft' },
    licenses:    [{ id: 1, name: '1 ПК / 1 год', price: '15000.00', in_stock: true }],
}

describe('ProductCard', () => {
    it('renders product name', () => {
        const wrapper = mount(ProductCard, {
            props: { product: mockProduct },
            global: {
                plugins: [createTestingPinia()], // Pinia в тестовом режиме
            }
        })

        // Проверяем, что имя товара отображается в шаблоне
        expect(wrapper.text()).toContain('Microsoft Office 365')
    })

    it('shows HIT badge when is_hit is true', () => {
        const wrapper = mount(ProductCard, {
            props: { product: { ...mockProduct, is_hit: true } },
            global: { plugins: [createTestingPinia()] },
        })

        expect(wrapper.text()).toContain('ХИТ')
    })

    it('does not show HIT badge when is_hit is false', () => {
        const wrapper = mount(ProductCard, {
            props: { product: { ...mockProduct, is_hit: false } },
            global: { plugins: [createTestingPinia()] },
        })

        expect(wrapper.text()).not.toContain('ХИТ')
    })

    it('shows price', () => {
        const wrapper = mount(ProductCard, {
            props: { product: mockProduct },
            global: { plugins: [createTestingPinia()] },
        })

        expect(wrapper.text()).toContain('15')  // часть цены
    })

    it('add to cart button is clickable', async () => {
        const wrapper = mount(ProductCard, {
            props: { product: mockProduct },
            global: { plugins: [createTestingPinia({ stubActions: false })] },
        })

        const button = wrapper.find('button')
        expect(button.exists()).toBe(true)
        expect(button.text()).toContain('В корзину')
    })
})
```

### Запуск Vue-тестов:

```bash
npm run test:vue
```

---

# Финальный чеклист перед запуском

## Backend

```bash
# 1. Все миграции применены
php artisan migrate:status

# 2. База заполнена данными
php artisan db:seed

# 3. Конфигурация в порядке
php artisan config:cache

# 4. Маршруты зарегистрированы
php artisan route:list --path=api

# 5. Тесты проходят
php artisan test
```

## Frontend

```bash
# 1. Зависимости установлены
npm install

# 2. Сборка работает
npm run build

# 3. Vue-тесты проходят
npm run test:vue
```

## Запуск для разработки

```bash
# Терминал 1
php artisan serve

# Терминал 2
npm run dev
```

Открой браузер: **http://localhost:5173**

---

# Итоговая структура проекта

```
asoft.kz/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── VendorController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CartController.php
│   │   │   ├── FavoriteController.php
│   │   │   └── OrderController.php
│   │   └── Middleware/
│   │       └── CheckRole.php
│   └── Models/
│       ├── User.php
│       ├── Category.php
│       ├── Vendor.php
│       ├── Product.php
│       ├── ProductLicense.php
│       ├── ProductImage.php
│       ├── CartItem.php
│       ├── Favorite.php
│       ├── Order.php
│       └── OrderItem.php
├── database/
│   ├── migrations/   # 10 файлов миграций
│   ├── factories/    # ProductFactory, CategoryFactory
│   └── seeders/      # DatabaseSeeder, CategorySeeder, VendorSeeder, ProductSeeder
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.js
│   │   ├── App.vue
│   │   ├── router/index.js
│   │   ├── stores/
│   │   │   ├── auth.js
│   │   │   ├── cart.js
│   │   │   └── catalog.js
│   │   ├── pages/
│   │   │   ├── HomePage.vue
│   │   │   ├── CatalogPage.vue
│   │   │   ├── ProductPage.vue
│   │   │   ├── CartPage.vue
│   │   │   ├── CheckoutPage.vue
│   │   │   ├── LoginPage.vue
│   │   │   ├── RegisterPage.vue
│   │   │   ├── AccountPage.vue
│   │   │   ├── FavoritesPage.vue
│   │   │   └── NotFoundPage.vue
│   │   ├── components/
│   │   │   ├── layout/
│   │   │   │   ├── AppHeader.vue
│   │   │   │   └── AppFooter.vue
│   │   │   ├── catalog/
│   │   │   │   └── ProductCard.vue
│   │   │   └── ui/
│   │   │       └── BasePagination.vue
│   │   └── tests/
│   │       └── ProductCard.test.js
│   └── views/app.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   └── Feature/
│       ├── AuthTest.php
│       └── ProductTest.php
├── .env
├── vite.config.js
├── tailwind.config.js
└── phpunit.xml
```

---

*Руководство состоит из 5 файлов: GUIDE_PART1.md — GUIDE_PART5.md*
