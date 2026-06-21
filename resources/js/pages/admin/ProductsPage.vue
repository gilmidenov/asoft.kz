<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const products    = ref([])
const categories  = ref([])
const vendors     = ref([])
const loading     = ref(true)
const showModal   = ref(false)
const editMode    = ref(false)
const saving      = ref(false)
const errors      = ref({})
const pagination  = ref(null)
const page        = ref(1)

const emptyForm = () => ({
    name: '', category_id: '', vendor_id: '', short_description: '',
    description: '', version: '', language: '', delivery_type: 'key',
    status: 'active', is_hit: false, is_new: false, is_sale: false,
})

const form = ref(emptyForm())
const editingId = ref(null)

const deliveryLabels = { download: 'Загрузка', box: 'Коробка', key: 'Ключ' }
const statusLabels   = { active: { label: 'Активен', color: 'text-green-600' }, inactive: { label: 'Неактивен', color: 'text-gray-400' }, out_of_stock: { label: 'Нет в наличии', color: 'text-red-500' } }

async function load() {
    loading.value = true
    try {
        const { data } = await axios.get('/products', { params: { page: page.value, per_page: 20 } })
        products.value  = data.data
        pagination.value = data
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    const [catRes, venRes] = await Promise.all([axios.get('/categories'), axios.get('/vendors')])
    categories.value = catRes.data
    vendors.value    = venRes.data
    await load()
})

function openCreate() {
    editMode.value  = false
    editingId.value = null
    form.value      = emptyForm()
    errors.value    = {}
    showModal.value = true
}

function openEdit(product) {
    editMode.value  = true
    editingId.value = product.id
    form.value      = {
        name:              product.name,
        category_id:       product.category_id || '',
        vendor_id:         product.vendor_id || '',
        short_description: product.short_description || '',
        description:       product.description || '',
        version:           product.version || '',
        language:          product.language || '',
        delivery_type:     product.delivery_type || 'key',
        status:            product.status || 'active',
        is_hit:            !!product.is_hit,
        is_new:            !!product.is_new,
        is_sale:           !!product.is_sale,
    }
    errors.value    = {}
    showModal.value = true
}

async function save() {
    saving.value = true
    errors.value = {}
    try {
        if (editMode.value) {
            await axios.put(`/admin/products/${editingId.value}`, form.value)
        } else {
            await axios.post('/admin/products', form.value)
        }
        showModal.value = false
        await load()
    } catch (e) {
        errors.value = e.response?.data?.errors || { general: [e.response?.data?.message || 'Ошибка'] }
    } finally {
        saving.value = false
    }
}

async function remove(id) {
    if (!confirm('Удалить товар?')) return
    await axios.delete(`/admin/products/${id}`)
    await load()
}
</script>

<template>
    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Товары</h1>
            <button @click="openCreate" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                + Добавить товар
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-muted">Загрузка...</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Название</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Категория</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Вендор</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Метки</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="p in products" :key="p.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">{{ p.name }}</td>
                        <td class="px-6 py-4 text-muted">{{ p.category?.name || '—' }}</td>
                        <td class="px-6 py-4 text-muted">{{ p.vendor?.name || '—' }}</td>
                        <td class="px-6 py-4">
                            <span :class="statusLabels[p.status]?.color" class="font-medium text-xs">
                                {{ statusLabels[p.status]?.label }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="p.is_hit"  class="inline-block bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded mr-1">Хит</span>
                            <span v-if="p.is_new"  class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded mr-1">Новинка</span>
                            <span v-if="p.is_sale" class="inline-block bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded">Акция</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openEdit(p)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                            <button @click="remove(p.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                        </td>
                    </tr>
                    <tr v-if="products.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-muted">Товаров пока нет</td>
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

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-dark">{{ editMode ? 'Редактировать товар' : 'Новый товар' }}</h2>
                    <button @click="showModal = false" class="text-muted hover:text-dark">&times;</button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Название *</label>
                        <input v-model="form.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Категория</label>
                            <select v-model="form.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                                <option value="">— Не выбрана —</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Вендор</label>
                            <select v-model="form.vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                                <option value="">— Не выбран —</option>
                                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Краткое описание</label>
                        <input v-model="form.short_description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Описание</label>
                        <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Версия</label>
                            <input v-model="form.version" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Язык</label>
                            <input v-model="form.language" placeholder="Русский" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Доставка</label>
                            <select v-model="form.delivery_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                                <option v-for="(label, val) in deliveryLabels" :key="val" :value="val">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Статус</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                            <option value="active">Активен</option>
                            <option value="inactive">Неактивен</option>
                            <option value="out_of_stock">Нет в наличии</option>
                        </select>
                    </div>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" v-model="form.is_hit" class="rounded" /> Хит продаж
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" v-model="form.is_new" class="rounded" /> Новинка
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" v-model="form.is_sale" class="rounded" /> Акция
                        </label>
                    </div>
                    <div v-if="errors.general" class="text-red-500 text-sm">{{ errors.general[0] }}</div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="saving" class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 disabled:opacity-60 transition-colors">
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                        <button type="button" @click="showModal = false" class="flex-1 border border-gray-300 text-muted py-2.5 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
