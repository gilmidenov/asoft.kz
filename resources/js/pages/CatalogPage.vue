<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import ProductCard from '@/components/catalog/ProductCard.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

const route  = useRoute()
const router = useRouter()

const products   = ref([])
const pagination = ref(null)
const loading    = ref(false)

const filters = ref({
    search:     route.query.search     || '',
    sort:       route.query.sort       || 'default',
    price_from: route.query.price_from || '',
    price_to:   route.query.price_to   || '',
    is_hit:     route.query.is_hit     === '1',
    is_sale:    route.query.is_sale    === '1',
    vendor:     route.query.vendor     || '',
    page:       route.query.page ? Number(route.query.page) : 1,
})

const categorySlug = computed(() => route.params.slug || null)

async function loadProducts() {
    loading.value = true
    try {
        const params = {
            ...filters.value,
            is_hit:   filters.value.is_hit   ? 1 : undefined,
            is_sale:  filters.value.is_sale  ? 1 : undefined,
            category: categorySlug.value     || undefined,
            vendor:   filters.value.vendor   || undefined,
        }
        const { data } = await axios.get('/products', { params })
        products.value   = data.data
        pagination.value = data
    } finally {
        loading.value = false
    }
}

watch(filters, (newFilters) => {
    router.replace({
        query: {
            ...newFilters,
            is_hit:  newFilters.is_hit   ? '1' : undefined,
            is_sale: newFilters.is_sale  ? '1' : undefined,
            vendor:  newFilters.vendor   || undefined,
        }
    })
    loadProducts()
}, { deep: true })

watch(() => route.params.slug, () => loadProducts())

watch(() => route.query.vendor, (newVendor) => {
    if (filters.value.vendor !== (newVendor || '')) {
        filters.value.vendor = newVendor || ''
    }
})

onMounted(() => loadProducts())

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
            <!-- Фильтры -->
            <aside class="hidden lg:block w-56 flex-shrink-0">
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Поиск</label>
                    <input v-model="filters.search" type="text" placeholder="Название программы..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Сортировка</label>
                    <select v-model="filters.sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                        <option value="default">По популярности</option>
                        <option value="price_asc">Сначала дешевле</option>
                        <option value="price_desc">Сначала дороже</option>
                        <option value="name_asc">По названию А-Я</option>
                        <option value="new">Сначала новые</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Цена (₸)</label>
                    <div class="flex gap-2">
                        <input v-model="filters.price_from" type="number" placeholder="от"
                            class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <input v-model="filters.price_to" type="number" placeholder="до"
                            class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-dark mb-2">Фильтры</label>
                    <label class="flex items-center gap-2 cursor-pointer mb-2">
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

            <!-- Товары -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold text-dark">{{ categorySlug ? 'Категория' : 'Все программы' }}</h1>
                    <span v-if="pagination" class="text-sm text-muted">Найдено: {{ pagination.total }} товаров</span>
                </div>

                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="i in 12" :key="i" class="bg-white rounded-xl h-72 animate-pulse border border-gray-100" />
                </div>

                <div v-else-if="products.length === 0" class="text-center py-16 text-muted">
                    <div class="text-6xl mb-4">🔍</div>
                    <p class="text-lg font-medium">Ничего не найдено</p>
                    <button @click="resetFilters" class="mt-4 text-primary hover:underline text-sm">Сбросить фильтры</button>
                </div>

                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>

                <BasePagination v-if="pagination && pagination.last_page > 1"
                    :current-page="pagination.current_page"
                    :last-page="pagination.last_page"
                    class="mt-8"
                    @change="changePage" />
            </div>
        </div>
    </div>
</template>
