<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const vendors   = ref([])
const loading   = ref(true)
const showModal = ref(false)
const editMode  = ref(false)
const saving    = ref(false)
const errors    = ref({})
const editingId = ref(null)

const emptyForm = () => ({ name: '', description: '', logo: '', website: '', is_active: true })
const form = ref(emptyForm())

async function load() {
    loading.value = true
    try {
        const { data } = await axios.get('/vendors')
        vendors.value = data
    } finally {
        loading.value = false
    }
}

onMounted(load)

function openCreate() {
    editMode.value  = false
    editingId.value = null
    form.value      = emptyForm()
    errors.value    = {}
    showModal.value = true
}

function openEdit(vendor) {
    editMode.value  = true
    editingId.value = vendor.id
    form.value      = { name: vendor.name, description: vendor.description || '', logo: vendor.logo || '', website: vendor.website || '', is_active: !!vendor.is_active }
    errors.value    = {}
    showModal.value = true
}

async function save() {
    saving.value = true
    errors.value = {}
    try {
        if (editMode.value) {
            await axios.put(`/admin/vendors/${editingId.value}`, form.value)
        } else {
            await axios.post('/admin/vendors', form.value)
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
    if (!confirm('Удалить вендора?')) return
    await axios.delete(`/admin/vendors/${id}`)
    await load()
}
</script>

<template>
    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Вендоры</h1>
            <button @click="openCreate" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                + Добавить вендора
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-muted">Загрузка...</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Вендор</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Сайт</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="v in vendors" :key="v.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ v.name }}</div>
                            <div class="text-muted text-xs mt-0.5">{{ v.description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <a v-if="v.website" :href="v.website" target="_blank" class="text-primary hover:underline text-xs">{{ v.website }}</a>
                            <span v-else class="text-muted text-xs">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="v.is_active ? 'text-green-600' : 'text-gray-400'" class="font-medium text-xs">
                                {{ v.is_active ? 'Активен' : 'Скрыт' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openEdit(v)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                            <button @click="remove(v.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                        </td>
                    </tr>
                    <tr v-if="vendors.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-muted">Вендоров пока нет</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-dark">{{ editMode ? 'Редактировать вендора' : 'Новый вендор' }}</h2>
                    <button @click="showModal = false" class="text-muted hover:text-dark text-xl">&times;</button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Название *</label>
                        <input v-model="form.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Описание</label>
                        <textarea v-model="form.description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Логотип (URL)</label>
                        <input v-model="form.logo" type="text" placeholder="https://..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Сайт</label>
                        <input v-model="form.website" type="url" placeholder="https://microsoft.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <p v-if="errors.website" class="text-red-500 text-xs mt-1">{{ errors.website[0] }}</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" v-model="form.is_active" class="rounded" /> Активен (отображается на сайте)
                    </label>
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
