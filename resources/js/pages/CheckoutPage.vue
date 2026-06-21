<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router    = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const form    = ref({ comment: '' })
const loading = ref(false)
const error   = ref('')

function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', { style: 'currency', currency: 'KZT', minimumFractionDigits: 0 }).format(price)
}

async function placeOrder() {
    error.value   = ''
    loading.value = true
    try {
        const { data } = await axios.post('/orders', { comment: form.value.comment })
        await cartStore.fetchCart()
        router.push({ name: 'account' })
    } catch (e) {
        error.value = e.response?.data?.message || 'Ошибка при оформлении заказа'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-dark mb-6">Оформление заказа</h1>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Форма -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="font-bold text-dark mb-4">Контактные данные</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Имя:</span>
                            <span class="font-medium text-dark">{{ authStore.user?.name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Email (для ключей):</span>
                            <span class="font-medium text-dark">{{ authStore.user?.email }}</span>
                        </div>
                        <div v-if="authStore.user?.phone" class="flex justify-between text-sm">
                            <span class="text-muted">Телефон:</span>
                            <span class="font-medium text-dark">{{ authStore.user.phone }}</span>
                        </div>
                    </div>
                    <RouterLink to="/account" class="text-sm text-primary hover:underline mt-3 inline-block">
                        Изменить данные профиля
                    </RouterLink>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="font-bold text-dark mb-4">Состав заказа</h2>
                    <div class="space-y-3">
                        <div v-for="item in cartStore.items" :key="item.id" class="flex justify-between text-sm">
                            <div>
                                <p class="font-medium text-dark">{{ item.product?.name }}</p>
                                <p class="text-muted text-xs">{{ item.license?.name }}</p>
                            </div>
                            <span class="font-medium text-dark">
                                {{ formatPrice(item.license?.price) }}
                                <span v-if="item.quantity > 1" class="text-muted text-xs"> × {{ item.quantity }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="font-bold text-dark mb-4">Комментарий к заказу <span class="text-muted font-normal text-sm">(необязательно)</span></h2>
                    <textarea v-model="form.comment" rows="3" placeholder="Дополнительные пожелания..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-colors resize-none" />
                </div>
            </div>

            <!-- Итог -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-100 p-5 sticky top-24">
                    <h2 class="font-bold text-dark text-lg mb-4">Итог заказа</h2>
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-muted">Товаров:</span>
                            <span>{{ cartStore.count }} шт.</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Доставка:</span>
                            <span class="text-green-600 font-medium">Бесплатно (email)</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 mt-2">
                            <span class="font-semibold text-dark">К оплате:</span>
                            <span class="font-bold text-xl text-accent">{{ formatPrice(cartStore.total) }}</span>
                        </div>
                    </div>

                    <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-3">
                        {{ error }}
                    </div>

                    <button @click="placeOrder" :disabled="loading || cartStore.items.length === 0"
                        class="w-full bg-accent text-white py-3 rounded-xl font-semibold hover:bg-orange-600 disabled:opacity-60 transition-colors">
                        {{ loading ? 'Оформляем...' : 'Подтвердить заказ' }}
                    </button>

                    <div class="mt-4 space-y-2 text-xs text-muted">
                        <p>🔑 Ключи активации отправим на email</p>
                        <p>⚡ Доставка в течение 1 часа</p>
                        <p>🔒 Оплата через защищённое соединение</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
