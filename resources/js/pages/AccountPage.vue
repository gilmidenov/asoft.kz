<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router    = useRouter()
const orders    = ref([])

onMounted(async () => {
    const { data } = await axios.get('/orders')
    orders.value = data.data
})

function formatPrice(price) {
    return new Intl.NumberFormat('ru-KZ', { style: 'currency', currency: 'KZT', minimumFractionDigits: 0 }).format(price)
}

async function logout() {
    await authStore.logout()
    router.push({ name: 'home' })
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Личный кабинет</h1>
            <button @click="logout" class="text-sm text-red-500 hover:underline">Выйти</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
            <p class="font-medium">{{ authStore.user?.name }}</p>
            <p class="text-muted text-sm">{{ authStore.user?.email }}</p>
        </div>

        <h2 class="text-lg font-bold text-dark mb-4">Мои заказы</h2>

        <div v-if="orders.length === 0" class="text-muted text-sm">Заказов пока нет.</div>

        <div v-else class="space-y-3">
            <div v-for="order in orders" :key="order.id"
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-dark">{{ order.order_number }}</p>
                    <p class="text-xs text-muted">{{ new Date(order.created_at).toLocaleDateString('ru-RU') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-accent">{{ formatPrice(order.total) }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full"
                        :class="{
                            'bg-yellow-100 text-yellow-700': order.status === 'pending',
                            'bg-green-100 text-green-700':  order.status === 'completed',
                            'bg-blue-100 text-blue-700':    order.status === 'paid',
                        }">
                        {{ order.status }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
