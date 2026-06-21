<script setup>
import { ref } from 'vue'
import { useCartStore } from '@/stores/cart'

const props = defineProps({
    product: { type: Object, required: true }
})

const cartStore = useCartStore()
const adding    = ref(false)

async function addToCart() {
    const license = props.product.licenses?.[0]
    if (!license) return
    adding.value = true
    try {
        await cartStore.addItem(license.id)
    } finally {
        adding.value = false
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', { style: 'currency', currency: 'KZT', minimumFractionDigits: 0 }).format(price)
}
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col">

        <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="block">
            <div class="relative aspect-square p-4 flex items-center justify-center bg-gray-50 rounded-t-xl overflow-hidden">
                <img v-if="product.main_image" :src="product.main_image" :alt="product.name" class="w-full h-full object-contain" />
                <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                    </svg>
                </div>
                <div class="absolute top-2 left-2 flex flex-col gap-1">
                    <span v-if="product.is_hit"  class="bg-accent   text-white text-xs font-bold px-2 py-0.5 rounded">ХИТ</span>
                    <span v-if="product.is_new"  class="bg-primary  text-white text-xs font-bold px-2 py-0.5 rounded">НОВИНКА</span>
                    <span v-if="product.is_sale" class="bg-red-500  text-white text-xs font-bold px-2 py-0.5 rounded">АКЦИЯ</span>
                </div>
            </div>
        </RouterLink>

        <div class="p-4 flex flex-col flex-1">
            <span v-if="product.vendor" class="text-xs text-muted mb-1 uppercase tracking-wide">
                {{ product.vendor.short_name || product.vendor.name }}
            </span>
            <RouterLink :to="{ name: 'product', params: { slug: product.slug } }"
                class="text-dark font-medium text-sm hover:text-primary transition-colors leading-snug mb-2 line-clamp-2">
                {{ product.name }}
            </RouterLink>
            <span v-if="product.licenses?.length" class="text-xs text-muted mb-3">
                {{ product.licenses[0].name }}
            </span>
            <div class="flex-1" />
            <div class="flex items-center justify-between gap-2 mt-2">
                <div>
                    <template v-if="product.price_from">
                        <span class="text-xs text-muted">от </span>
                        <span class="text-lg font-bold text-accent">{{ formatPrice(product.price_from) }}</span>
                    </template>
                    <span v-else class="text-sm text-muted">Под запрос</span>
                </div>
                <button @click.prevent="addToCart" :disabled="adding || !product.licenses?.length"
                    class="bg-primary text-white text-sm px-3 py-1.5 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                    <span v-if="adding">...</span>
                    <span v-else>В корзину</span>
                </button>
            </div>
        </div>
    </div>
</template>
