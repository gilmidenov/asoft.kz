<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const vendor  = ref(null)
const total   = ref(0)
const loading = ref(true)

const pluralWord = computed(() => {
    const n = total.value
    if (n % 10 === 1 && n % 100 !== 11) return 'товар'
    if (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) return 'товара'
    return 'товаров'
})

onMounted(async () => {
    loading.value = true
    try {
        const [vendorRes, countRes] = await Promise.all([
            axios.get(`/vendors/${route.params.slug}`),
            axios.get('/products', { params: { vendor: route.params.slug, per_page: 1 } }),
        ])
        vendor.value = vendorRes.data
        total.value  = countRes.data.total
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

        <!-- Скелетон -->
        <div v-if="loading" class="animate-pulse bg-white rounded-2xl h-52 border border-gray-100" />

        <!-- Карточка -->
        <template v-else-if="vendor">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <div class="flex flex-col sm:flex-row items-start gap-8">

                    <!-- Логотип -->
                    <div class="w-36 h-36 rounded-2xl bg-primary-50 border border-primary-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <img v-if="vendor.logo" :src="vendor.logo" :alt="vendor.name"
                            class="w-full h-full object-contain p-3" />
                        <span v-else class="text-primary font-bold text-5xl select-none">
                            {{ vendor.name[0] }}
                        </span>
                    </div>

                    <!-- Инфо -->
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-dark mb-2">{{ vendor.name }}</h1>

                        <a v-if="vendor.website" :href="vendor.website" target="_blank" rel="noopener"
                            class="text-primary text-sm hover:underline inline-flex items-center gap-1 mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            {{ vendor.website }}
                        </a>

                        <p v-if="vendor.description" class="text-muted text-base leading-relaxed mb-6">
                            {{ vendor.description }}
                        </p>
                        <p v-else class="text-muted text-sm italic mb-6">Описание не указано</p>

                        <!-- Количество + кнопка -->
                        <div class="flex items-center gap-4">
                            <span class="text-dark font-semibold text-sm">
                                {{ total }} {{ pluralWord }}
                            </span>
                            <RouterLink
                                :to="{ name: 'catalog', query: { vendor: vendor.slug } }"
                                class="inline-flex items-center gap-2 bg-primary text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                                Товары
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </RouterLink>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- 404 -->
        <div v-else class="text-center py-20 text-muted">
            <div class="text-5xl mb-4">🔍</div>
            <p class="text-lg font-medium">Вендор не найден</p>
            <RouterLink to="/vendors" class="mt-4 inline-block text-primary hover:underline text-sm">
                Вернуться к вендорам
            </RouterLink>
        </div>
    </div>
</template>
