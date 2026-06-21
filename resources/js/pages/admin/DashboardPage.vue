<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const stats   = ref({ products: 0, categories: 0, vendors: 0, orders: 0 })
const loading = ref(true)
const orders  = ref([])

const statusLabels = {
    pending:    { label: 'Ожидает', color: 'bg-yellow-100 text-yellow-800' },
    paid:       { label: 'Оплачен', color: 'bg-blue-100 text-blue-800' },
    processing: { label: 'Обрабатывается', color: 'bg-purple-100 text-purple-800' },
    completed:  { label: 'Выполнен', color: 'bg-green-100 text-green-800' },
    cancelled:  { label: 'Отменён', color: 'bg-red-100 text-red-800' },
    refunded:   { label: 'Возврат', color: 'bg-gray-100 text-gray-800' },
}

onMounted(async () => {
    try {
        const [productsRes, categoriesRes, vendorsRes, ordersRes] = await Promise.all([
            axios.get('/products', { params: { per_page: 1 } }),
            axios.get('/categories'),
            axios.get('/vendors'),
            axios.get('/admin/orders', { params: { per_page: 5 } }),
        ])
        stats.value.products   = productsRes.data.total
        stats.value.categories = categoriesRes.data.length
        stats.value.vendors    = vendorsRes.data.length
        stats.value.orders     = ordersRes.data.total
        orders.value           = ordersRes.data.data
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="p-8">
        <h1 class="text-2xl font-bold text-dark mb-8">Дашборд</h1>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <p class="text-muted text-sm mb-1">Товаров</p>
                <p class="text-3xl font-bold text-dark">{{ stats.products }}</p>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <p class="text-muted text-sm mb-1">Категорий</p>
                <p class="text-3xl font-bold text-dark">{{ stats.categories }}</p>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <p class="text-muted text-sm mb-1">Вендоров</p>
                <p class="text-3xl font-bold text-dark">{{ stats.vendors }}</p>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <p class="text-muted text-sm mb-1">Заказов</p>
                <p class="text-3xl font-bold text-dark">{{ stats.orders }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-dark">Последние заказы</h2>
                <RouterLink to="/admin/orders" class="text-primary text-sm hover:underline">Все заказы →</RouterLink>
            </div>
            <div v-if="loading" class="p-6 text-center text-muted text-sm">Загрузка...</div>
            <div v-else-if="orders.length === 0" class="p-6 text-center text-muted text-sm">Заказов пока нет</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Номер</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Клиент</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Сумма</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Дата</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono font-medium">{{ order.order_number }}</td>
                        <td class="px-6 py-4">{{ order.customer_name }}</td>
                        <td class="px-6 py-4">{{ Number(order.total).toLocaleString('ru') }} ₸</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[order.status]?.color">
                                {{ statusLabels[order.status]?.label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-muted">{{ new Date(order.created_at).toLocaleDateString('ru') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
