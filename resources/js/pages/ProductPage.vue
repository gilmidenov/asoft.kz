<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const route     = useRoute()
const cartStore = useCartStore()

const product         = ref(null)
const loading         = ref(true)
const selectedLicense = ref(null)
const adding          = ref(false)
const addedToCart     = ref(false)

onMounted(async () => {
    try {
        const { data } = await axios.get(`/products/${route.params.slug}`)
        product.value = data
        selectedLicense.value = data.licenses?.[0] || null
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
        <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-48 mb-8" />
            <div class="grid md:grid-cols-2 gap-8">
                <div class="aspect-square bg-gray-200 rounded-xl" />
                <div class="space-y-4">
                    <div class="h-8 bg-gray-200 rounded w-3/4" />
                    <div class="h-4 bg-gray-200 rounded" />
                </div>
            </div>
        </div>

        <template v-else-if="product">
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

            <div class="grid md:grid-cols-2 gap-8 mb-10">
                <div class="bg-white rounded-xl border border-gray-100 p-8 flex items-center justify-center aspect-square">
                    <img v-if="product.main_image" :src="product.main_image" :alt="product.name" class="max-h-64 object-contain" />
                    <div v-else class="w-32 h-32 bg-gray-100 rounded-xl" />
                </div>

                <div>
                    <div v-if="product.vendor" class="mb-3">
                        <span class="text-sm font-semibold text-primary">{{ product.vendor.name }}</span>
                    </div>

                    <h1 class="text-2xl font-bold text-dark mb-2">{{ product.name }}</h1>

                    <div class="flex gap-2 mb-4">
                        <span v-if="product.is_hit"    class="bg-accent/10   text-accent   text-xs font-bold px-2 py-1 rounded">ХИТ</span>
                        <span v-if="product.is_new"    class="bg-primary/10  text-primary  text-xs font-bold px-2 py-1 rounded">НОВИНКА</span>
                        <span v-if="product.language"  class="bg-gray-100    text-muted    text-xs px-2 py-1 rounded">{{ product.language }}</span>
                        <span v-if="product.version"   class="bg-gray-100    text-muted    text-xs px-2 py-1 rounded">v{{ product.version }}</span>
                    </div>

                    <p v-if="product.short_description" class="text-muted mb-6">{{ product.short_description }}</p>

                    <div v-if="product.licenses?.length" class="mb-6">
                        <h3 class="font-semibold text-dark mb-3">Выберите лицензию:</h3>
                        <div class="space-y-2">
                            <label v-for="license in product.licenses" :key="license.id"
                                class="flex items-center justify-between p-3 border-2 rounded-xl cursor-pointer transition-all"
                                :class="selectedLicense?.id === license.id ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300'">
                                <div class="flex items-center gap-3">
                                    <input type="radio" :value="license" v-model="selectedLicense" class="text-primary" />
                                    <div>
                                        <div class="font-medium text-dark text-sm">{{ license.name }}</div>
                                        <div v-if="license.duration_months" class="text-xs text-muted">{{ license.duration_months }} мес.</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div v-if="license.old_price" class="text-xs text-muted line-through">{{ formatPrice(license.old_price) }}</div>
                                    <div class="font-bold text-accent">{{ formatPrice(license.price) }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button @click="addToCart" :disabled="adding || !selectedLicense || !selectedLicense.in_stock"
                        class="w-full py-3 px-6 rounded-xl font-semibold text-white transition-all"
                        :class="addedToCart ? 'bg-green-500' : 'bg-primary hover:bg-primary-700 disabled:opacity-50'">
                        <span v-if="addedToCart">✓ Добавлено в корзину!</span>
                        <span v-else-if="adding">Добавляем...</span>
                        <span v-else-if="!selectedLicense?.in_stock">Под запрос</span>
                        <span v-else>В корзину</span>
                    </button>

                    <div class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-muted">
                        🔑 Ключ активации на email в течение 1 часа
                    </div>
                </div>
            </div>

            <div v-if="product.description" class="bg-white rounded-xl border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-dark mb-4">Описание</h2>
                <div class="prose max-w-none text-muted" v-html="product.description" />
            </div>
        </template>

        <div v-else class="text-center py-16">
            <div class="text-6xl mb-4">😕</div>
            <h2 class="text-xl font-bold text-dark mb-2">Товар не найден</h2>
            <RouterLink to="/catalog" class="text-primary hover:underline">Вернуться в каталог</RouterLink>
        </div>
    </div>
</template>
