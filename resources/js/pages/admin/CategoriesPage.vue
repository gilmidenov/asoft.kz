<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()

const categories   = ref([])
const loading      = ref(true)
const showModal    = ref(false)
const editMode     = ref(false)
const saving       = ref(false)
const errors       = ref({})
const editingId    = ref(null)

const imageFile    = ref(null)
const imagePreview = ref(null)
const currentImage = ref(null)
const imageInputRef = ref(null)

const emptyForm = () => ({ name: '', parent_id: '', description: '', sort_order: 0, is_active: true })
const form = ref(emptyForm())

async function load() {
    loading.value = true
    try {
        const { data } = await axios.get('/categories')
        categories.value = data
    } finally {
        loading.value = false
    }
}

onMounted(load)

function resetImageState() {
    imageFile.value    = null
    imagePreview.value = null
    currentImage.value = null
    if (imageInputRef.value) imageInputRef.value.value = ''
}

function openCreate() {
    editMode.value  = false
    editingId.value = null
    form.value      = emptyForm()
    errors.value    = {}
    resetImageState()
    showModal.value = true
}

function openEdit(cat) {
    editMode.value  = true
    editingId.value = cat.id
    form.value      = { name: cat.name, parent_id: cat.parent_id || '', description: cat.description || '', sort_order: cat.sort_order || 0, is_active: !!cat.is_active }
    errors.value    = {}
    resetImageState()
    currentImage.value = cat.image || null
    showModal.value = true
}

function onImagePicked(e) {
    const file = e.target.files[0]
    if (!file) return
    imageFile.value    = file
    imagePreview.value = URL.createObjectURL(file)
}

function clearImagePick() {
    imageFile.value    = null
    imagePreview.value = null
    if (imageInputRef.value) imageInputRef.value.value = ''
}

async function save() {
    saving.value = true
    errors.value = {}
    try {
        const payload = { ...form.value, parent_id: form.value.parent_id || null }
        let savedId = editingId.value

        if (editMode.value) {
            await axios.put(`/admin/categories/${savedId}`, payload)
        } else {
            const { data } = await axios.post('/admin/categories', payload)
            savedId = data.id
        }

        if (imageFile.value) {
            const fd = new FormData()
            fd.append('image', imageFile.value)
            await axios.post(`/admin/categories/${savedId}/image`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showModal.value = false
        await load()
        catalogStore.refreshCategories()
    } catch (e) {
        errors.value = e.response?.data?.errors || { general: [e.response?.data?.message || 'Ошибка'] }
    } finally {
        saving.value = false
    }
}

async function remove(id) {
    if (!confirm('Удалить категорию? Связанные товары останутся без категории.')) return
    await axios.delete(`/admin/categories/${id}`)
    await load()
    catalogStore.refreshCategories()
}
</script>

<template>
    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Категории</h1>
            <button @click="openCreate" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                + Добавить категорию
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-muted">Загрузка...</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase w-10"></th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Название</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Подкатегорий</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template v-for="cat in categories" :key="cat.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <img v-if="cat.image" :src="cat.image" class="w-8 h-8 object-cover rounded-lg border border-gray-200" />
                                <div v-else class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs font-bold">{{ cat.name[0] }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium">{{ cat.name }}</td>
                            <td class="px-6 py-4 text-muted font-mono text-xs">{{ cat.slug }}</td>
                            <td class="px-6 py-4 text-muted">{{ cat.children?.length || 0 }}</td>
                            <td class="px-6 py-4">
                                <span :class="cat.is_active ? 'text-green-600' : 'text-gray-400'" class="font-medium text-xs">
                                    {{ cat.is_active ? 'Активна' : 'Скрыта' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button @click="openEdit(cat)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                                <button @click="remove(cat.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                            </td>
                        </tr>
                        <tr v-for="child in cat.children" :key="child.id" class="bg-gray-50/50 hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <img v-if="child.image" :src="child.image" class="w-7 h-7 object-cover rounded-lg border border-gray-200 ml-2" />
                                <div v-else class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs ml-2">{{ child.name[0] }}</div>
                            </td>
                            <td class="px-6 py-3 text-muted pl-10 text-xs">└ {{ child.name }}</td>
                            <td class="px-6 py-3 text-muted font-mono text-xs">{{ child.slug }}</td>
                            <td class="px-6 py-3 text-muted text-xs">—</td>
                            <td class="px-6 py-3">
                                <span :class="child.is_active ? 'text-green-600' : 'text-gray-400'" class="font-medium text-xs">
                                    {{ child.is_active ? 'Активна' : 'Скрыта' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <button @click="openEdit(child)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                                <button @click="remove(child.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="categories.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-muted">Категорий пока нет</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                    <h2 class="font-bold text-dark">{{ editMode ? 'Редактировать категорию' : 'Новая категория' }}</h2>
                    <button @click="showModal = false" class="text-muted hover:text-dark text-xl">&times;</button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">

                    <!-- Изображение -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Изображение</label>
                        <div v-if="currentImage && !imagePreview" class="mb-3 flex items-start gap-3">
                            <img :src="currentImage" class="w-20 h-20 object-cover rounded-lg border border-gray-200" />
                            <span class="text-xs text-muted mt-1">Текущее изображение</span>
                        </div>
                        <div v-if="imagePreview" class="mb-3 flex items-start gap-3">
                            <img :src="imagePreview" class="w-20 h-20 object-cover rounded-lg border border-gray-200" />
                            <button type="button" @click="clearImagePick" class="text-xs text-red-500 hover:underline mt-1">Отменить выбор</button>
                        </div>
                        <label class="flex items-center justify-center w-full h-16 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors bg-gray-50">
                            <div class="text-center">
                                <p class="text-xs text-muted">{{ imagePreview ? 'Выбрать другое' : 'Нажмите для загрузки' }}</p>
                                <p class="text-xs text-gray-400">JPG, PNG, WebP — до 4 МБ</p>
                            </div>
                            <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onImagePicked" />
                        </label>
                        <p v-if="errors.image" class="text-red-500 text-xs mt-1">{{ errors.image[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Название *</label>
                        <input v-model="form.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Родительская категория</label>
                        <select v-model="form.parent_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary">
                            <option value="">— Корневая категория —</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Описание</label>
                        <textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Порядок сортировки</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" v-model="form.is_active" class="rounded" /> Активна (отображается на сайте)
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
