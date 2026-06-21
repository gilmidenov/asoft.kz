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

        <div v-if="cartStore.items.length === 0" class="text-center py-16">
            <div class="text-7xl mb-4">🛒</div>
            <h2 class="text-xl font-bold text-dark mb-2">Корзина пуста</h2>
            <p class="text-muted mb-6">Добавьте товары из каталога</p>
            <RouterLink to="/catalog" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:bg-primary-700 transition-colors">
                Перейти в каталог
            </RouterLink>
        </div>

        <div v-else class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div v-for="item in cartStore.items" :key="item.id"
                    class="bg-white rounded-xl border border-gray-100 p-4 flex gap-4">
                    <div class="w-20 h-20 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <img v-if="item.product?.main_image" :src="item.product.main_image" :alt="item.product.name" class="w-16 h-16 object-contain" />
                        <span v-else class="text-3xl">📦</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-medium text-dark">{{ item.product?.name }}</h3>
                        <p class="text-sm text-muted">{{ item.license?.name }}</p>
                        <p class="text-accent font-bold mt-1">{{ formatPrice(item.license?.price) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <button @click="cartStore.removeItem(item.id)" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="flex items-center gap-2">
                            <button @click="cartStore.updateItem(item.id, Math.max(1, item.quantity - 1))"
                                class="w-6 h-6 border rounded text-center hover:bg-gray-50">−</button>
                            <span class="text-sm font-medium w-6 text-center">{{ item.quantity }}</span>
                            <button @click="cartStore.updateItem(item.id, item.quantity + 1)"
                                class="w-6 h-6 border rounded text-center hover:bg-gray-50">+</button>
                        </div>
                    </div>
                </div>
                <button @click="cartStore.clearCart()" class="text-sm text-red-500 hover:underline">Очистить корзину</button>
            </div>

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
                    <p class="text-xs text-muted text-center mt-3">🔒 Безопасная оплата. Ключи активации на email.</p>
                </div>
            </div>
        </div>
    </div>
</template>
