<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const orders     = ref([])
const loading    = ref(true)
const pagination = ref(null)
const page       = ref(1)
const filterStatus = ref('')
const updating   = ref(null)

const statusLabels = {
    pending:    { label: 'Ожидает оплаты', color: 'bg-yellow-100 text-yellow-800' },
    paid:       { label: 'Оплачен', color: 'bg-blue-100 text-blue-800' },
    processing: { label: 'Обрабатывается', color: 'bg-purple-100 text-purple-800' },
    completed:  { label: 'Выполнен', color: 'bg-green-100 text-green-800' },
    cancelled:  { label: 'Отменён', color: 'bg-red-100 text-red-800' },
    refunded:   { label: 'Возврат', color: 'bg-gray-100 text-gray-800' },
}

const allStatuses = Object.entries(statusLabels).map(([val, info]) => ({ val, label: info.label }))

async function load() {
    loading.value = true
    try {
        const params = { page: page.value }
        if (filterStatus.value) params.status = filterStatus.value
        const { data } = await axios.get('/admin/orders', { params })
        orders.value     = data.data
        pagination.value = data
    } finally {
        loading.value = false
    }
}

onMounted(load)

async function updateStatus(order, status) {
    updating.value = order.id
    try {
        const { data } = await axios.patch(`/admin/orders/${order.id}/status`, { status })
        const idx = orders.value.findIndex(o => o.id === order.id)
        if (idx !== -1) orders.value[idx] = data
    } finally {
        updating.value = null
    }
}
</script>

<template>
    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Заказы</h1>
            <select v-model="filterStatus" @change="page = 1; load()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                <option value="">Все статусы</option>
                <option v-for="s in allStatuses" :key="s.val" :value="s.val">{{ s.label }}</option>
            </select>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-muted">Загрузка...</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Номер</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Клиент</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Сумма</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Товары</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Дата</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Изменить статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono font-medium text-xs">{{ order.order_number }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ order.customer_name }}</div>
                            <div class="text-muted text-xs">{{ order.customer_email }}</div>
                        </td>
                        <td class="px-6 py-4 font-semibold">{{ Number(order.total).toLocaleString('ru') }} ₸</td>
                        <td class="px-6 py-4 text-muted text-xs">
                            <div v-for="item in order.items" :key="item.id">{{ item.product_name }} × {{ item.quantity }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[order.status]?.color">
                                {{ statusLabels[order.status]?.label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-muted text-xs">{{ new Date(order.created_at).toLocaleString('ru') }}</td>
                        <td class="px-6 py-4">
                            <select :disabled="updating === order.id" :value="order.status"
                                @change="updateStatus(order, $event.target.value)"
                                class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:border-primary disabled:opacity-50">
                                <option v-for="s in allStatuses" :key="s.val" :value="s.val">{{ s.label }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr v-if="orders.length === 0">
                        <td colspan="7" class="px-6 py-10 text-center text-muted">Заказов нет</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex gap-2">
                <button v-for="p in pagination.last_page" :key="p" @click="page = p; load()"
                    class="w-8 h-8 rounded-lg text-sm font-medium transition-colors"
                    :class="p === pagination.current_page ? 'bg-primary text-white' : 'bg-gray-100 text-dark hover:bg-gray-200'">
                    {{ p }}
                </button>
            </div>
        </div>
    </div>
</template>
