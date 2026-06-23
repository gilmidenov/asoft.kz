<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import ProductCard from '@/components/catalog/ProductCard.vue'

const route = useRoute()

const vendor   = ref(null)
const products = ref([])
const loading  = ref(true)
const total    = ref(0)

onMounted(async () => {
    loading.value = true
    try {
        const [vendorRes, productsRes] = await Promise.all([
            axios.get(`/vendors/${route.params.slug}`),
            axios.get('/products', { params: { vendor: route.params.slug, per_page: 100 } }),
        ])
        vendor.value   = vendorRes.data
        products.value = productsRes.data.data
        total.value    = productsRes.data.total
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="container mx-auto px-4 py-8">

        <!-- Breadcrumb -->
        <nav class="text-sm text-muted mb-6">
            <RouterLink to="/" class="hover:text-primary">Главная</RouterLink>
            <span class="mx-2">/</span>
            <RouterLink to="/vendors" class="hover:text-primary">Вендоры</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-dark">{{ vendor?.name ?? route.params.slug }}</span>
        </nav>

        <!-- Загрузка -->
        <div v-if="loading" class="animate-pulse space-y-6">
            <div class="bg-white rounded-2xl h-40 border border-gray-100" />
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="i in 8" :key="i" class="bg-white rounded-xl h-72 border border-gray-100" />
            </div>
        </div>

        <template v-else-if="vendor">
            <!-- Карточка вендора -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8 flex flex-col sm:flex-row items-start gap-6">
                <!-- Логотип или инициал -->
                <div class="w-20 h-20 rounded-xl bg-primary-50 border border-primary-100 flex items-center justify-center flex-shrink-0">
                    <img v-if="vendor.logo" :src="vendor.logo" :alt="vendor.name" class="w-full h-full object-contain rounded-xl" />
                    <span v-else class="text-primary font-bold text-3xl">{{ vendor.name[0] }}</span>
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-dark mb-1">{{ vendor.name }}</h1>

                    <a v-if="vendor.website" :href="vendor.website" target="_blank" rel="noopener"
                        class="text-primary text-sm hover:underline inline-flex items-center gap-1 mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        {{ vendor.website }}
                    </a>

                    <p v-if="vendor.description" class="text-muted text-sm leading-relaxed">
                        {{ vendor.description }}
                    </p>
                    <p v-else class="text-muted text-sm italic">Описание не указано</p>
                </div>

                <div class="flex-shrink-0 text-right">
                    <div class="text-2xl font-bold text-dark">{{ total }}</div>
                    <div class="text-xs text-muted">{{ total === 1 ? 'товар' : total < 5 ? 'товара' : 'товаров' }}</div>
                </div>
            </div>

            <!-- Товары -->
            <div>
                <h2 class="text-xl font-bold text-dark mb-4">Товары {{ vendor.name }}</h2>

                <div v-if="products.length === 0" class="text-center py-16 text-muted">
                    <div class="text-5xl mb-3">📦</div>
                    <p class="font-medium">Товаров пока нет</p>
                </div>

                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
            </div>
        </template>

        <div v-else class="text-center py-20 text-muted">
            <div class="text-5xl mb-4">🔍</div>
            <p class="text-lg font-medium">Вендор не найден</p>
            <RouterLink to="/vendors" class="mt-4 inline-block text-primary hover:underline text-sm">
                Вернуться к вендорам
            </RouterLink>
        </div>
    </div>
</template>
