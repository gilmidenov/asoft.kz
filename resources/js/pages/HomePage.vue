<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProductCard from '@/components/catalog/ProductCard.vue'
import BannerSlider from '@/components/ui/BannerSlider.vue'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()
const hitProducts  = ref([])
const newProducts  = ref([])
const loading      = ref(false)

onMounted(async () => {
    loading.value = true
    try {
        await catalogStore.fetchCategories()
        const [hitsRes, newRes] = await Promise.all([
            axios.get('/products', { params: { is_hit: 1, per_page: 8 } }),
            axios.get('/products', { params: { sort: 'new', per_page: 8 } }),
        ])
        hitProducts.value = hitsRes.data.data
        newProducts.value = newRes.data.data
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div>
        <!-- БАННЕР-СЛАЙДЕР -->
        <BannerSlider />

        <!-- КАТЕГОРИИ -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-dark mb-6">Категории</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    <RouterLink v-for="cat in catalogStore.categories" :key="cat.id"
                        :to="{ name: 'category', params: { slug: cat.slug } }"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-primary hover:bg-primary-50 transition-all group text-center">
                        <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center group-hover:bg-primary transition-colors overflow-hidden">
                            <img v-if="cat.image" :src="cat.image" :alt="cat.name" class="w-full h-full object-contain" />
                            <span v-else class="text-primary group-hover:text-white font-bold text-lg">{{ cat.name[0] }}</span>
                        </div>
                        <span class="text-xs font-medium text-dark group-hover:text-primary leading-tight">{{ cat.name }}</span>
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- ХИТЫ -->
        <section class="py-12 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-dark">Хиты продаж <span class="text-accent">🔥</span></h2>
                    <RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }" class="text-primary hover:text-primary-700 text-sm font-medium">
                        Смотреть все →
                    </RouterLink>
                </div>
                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="i in 8" :key="i" class="bg-white rounded-xl h-72 animate-pulse" />
                </div>
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="p in hitProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- НОВИНКИ -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-dark">Новинки</h2>
                    <RouterLink :to="{ name: 'catalog', query: { sort: 'new' } }" class="text-primary hover:text-primary-700 text-sm font-medium">
                        Смотреть все →
                    </RouterLink>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard v-for="p in newProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- ПРЕИМУЩЕСТВА -->
        <section class="py-12 bg-header text-white">
            <div class="container mx-auto px-4">
                <div class="grid md:grid-cols-4 gap-8">
                    <div v-for="b in [
                        { icon: '🔑', title: 'Мгновенная доставка', text: 'Ключи активации на email в течение часа' },
                        { icon: '✅', title: 'Официальные лицензии', text: 'Только оригинальное ПО от авторизованных дистрибьюторов' },
                        { icon: '📞', title: 'Техподдержка', text: 'Помогаем с установкой и активацией' },
                        { icon: '💰', title: 'Лучшие цены', text: 'Прямые поставки без посредников' },
                    ]" :key="b.title" class="text-center">
                        <div class="text-4xl mb-3">{{ b.icon }}</div>
                        <h3 class="font-semibold text-lg mb-2">{{ b.title }}</h3>
                        <p class="text-gray-400 text-sm">{{ b.text }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
