<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const banners      = ref([])
const loading      = ref(true)
const showModal    = ref(false)
const editMode     = ref(false)
const saving       = ref(false)
const errors       = ref({})
const editingId    = ref(null)

const imageFile     = ref(null)
const imagePreview  = ref(null)
const currentImage  = ref(null)
const originalImage = ref(null)
const imageInputRef = ref(null)
const deletingImage = ref(false)

const emptyForm = () => ({
    title:       '',
    subtitle:    '',
    button_text: '',
    button_url:  '',
    sort_order:  0,
    is_active:   true,
})

const form = ref(emptyForm())

async function load() {
    loading.value = true
    try {
        const { data } = await axios.get('/admin/banners')
        banners.value = data
    } finally {
        loading.value = false
    }
}

onMounted(load)

function resetImageState() {
    imageFile.value     = null
    imagePreview.value  = null
    currentImage.value  = null
    originalImage.value = null
    deletingImage.value = false
    if (imageInputRef.value) imageInputRef.value.value = ''
}

function removeCurrentImage() {
    deletingImage.value = true
    currentImage.value  = null
    imageFile.value     = null
    imagePreview.value  = null
}

function undoRemoveImage() {
    deletingImage.value = false
    currentImage.value  = originalImage.value
}

function openCreate() {
    editMode.value  = false
    editingId.value = null
    form.value      = emptyForm()
    errors.value    = {}
    resetImageState()
    showModal.value = true
}

function openEdit(banner) {
    editMode.value  = true
    editingId.value = banner.id
    form.value = {
        title:       banner.title,
        subtitle:    banner.subtitle || '',
        button_text: banner.button_text || '',
        button_url:  banner.button_url || '',
        sort_order:  banner.sort_order || 0,
        is_active:   !!banner.is_active,
    }
    errors.value        = {}
    currentImage.value  = banner.image || null
    originalImage.value = banner.image || null
    deletingImage.value = false
    imageFile.value     = null
    imagePreview.value  = null
    if (imageInputRef.value) imageInputRef.value.value = ''
    showModal.value    = true
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
        let savedId = editingId.value

        if (editMode.value) {
            await axios.put(`/admin/banners/${savedId}`, form.value)
        } else {
            const { data } = await axios.post('/admin/banners', form.value)
            savedId = data.id
        }

        if (deletingImage.value && editMode.value) {
            await axios.delete(`/admin/banners/${savedId}/image`)
        } else if (imageFile.value) {
            const fd = new FormData()
            fd.append('image', imageFile.value)
            await axios.post(`/admin/banners/${savedId}/image`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
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
    if (!confirm('Удалить баннер?')) return
    await axios.delete(`/admin/banners/${id}`)
    await load()
}
</script>

<template>
    <div class="p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-dark">Баннеры главной страницы</h1>
            <button @click="openCreate" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                + Добавить баннер
            </button>
        </div>

        <p class="text-muted text-sm mb-6">Баннеры отображаются на главной странице в виде слайдера. Сортировка определяет порядок показа.</p>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-muted">Загрузка...</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase w-28">Фото</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Заголовок</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Кнопка</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Порядок</th>
                        <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="banner in banners" :key="banner.id" class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <img v-if="banner.image" :src="banner.image" class="w-24 h-14 object-cover rounded-lg border border-gray-200" />
                            <div v-else class="w-24 h-14 rounded-lg bg-gradient-to-br from-header to-primary-900 flex items-center justify-center">
                                <span class="text-white text-xs font-medium">Без фото</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-dark">{{ banner.title || '—' }}</p>
                            <p v-if="banner.subtitle" class="text-muted text-xs mt-0.5 line-clamp-1">{{ banner.subtitle }}</p>
                        </td>
                        <td class="px-6 py-4 text-muted text-xs">
                            <span v-if="banner.button_text">{{ banner.button_text }}</span>
                            <span v-else class="text-gray-300">—</span>
                        </td>
                        <td class="px-6 py-4 text-muted">{{ banner.sort_order }}</td>
                        <td class="px-6 py-4">
                            <span :class="banner.is_active ? 'text-green-600' : 'text-gray-400'" class="font-medium text-xs">
                                {{ banner.is_active ? 'Активен' : 'Скрыт' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openEdit(banner)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                            <button @click="remove(banner.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                        </td>
                    </tr>
                    <tr v-if="banners.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-muted">Баннеров пока нет. Добавьте первый!</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                    <h2 class="font-bold text-dark">{{ editMode ? 'Редактировать баннер' : 'Новый баннер' }}</h2>
                    <button @click="showModal = false" class="text-muted hover:text-dark text-xl">&times;</button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">

                    <!-- Изображение -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Изображение баннера</label>
                        <p class="text-xs text-muted mb-2">Рекомендуемый размер: 1920×600 px. Без изображения — используется градиентный фон.</p>

                        <!-- Текущее изображение -->
                        <div v-if="currentImage && !imagePreview" class="mb-3">
                            <img :src="currentImage" class="w-full h-32 object-cover rounded-lg border border-gray-200 mb-2" />
                            <button type="button" @click="removeCurrentImage"
                                class="flex items-center gap-1 text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Удалить изображение
                            </button>
                        </div>

                        <!-- Помечено к удалению -->
                        <div v-if="deletingImage" class="mb-3 flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            <span class="text-xs text-red-600">Изображение будет удалено при сохранении</span>
                            <button type="button" @click="undoRemoveImage" class="text-xs text-red-400 hover:text-red-600 ml-auto">Отменить</button>
                        </div>

                        <!-- Превью нового файла -->
                        <div v-if="imagePreview" class="mb-3 flex items-start gap-3">
                            <img :src="imagePreview" class="w-full h-32 object-cover rounded-lg border border-gray-200" />
                            <button type="button" @click="clearImagePick" class="text-xs text-red-500 hover:underline mt-1 flex-shrink-0">Отменить</button>
                        </div>

                        <label class="flex items-center justify-center w-full h-12 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors bg-gray-50">
                            <span class="text-xs text-muted">{{ imagePreview ? 'Выбрать другое' : currentImage ? 'Заменить изображение' : 'Нажмите для загрузки' }}</span>
                            <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onImagePicked" />
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">
                            Заголовок
                            <span class="text-gray-400 font-normal text-xs ml-1">(необязательно — если на картинке уже есть текст)</span>
                        </label>
                        <input v-model="form.title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="Лицензионное ПО для бизнеса" />
                        <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Подзаголовок</label>
                        <textarea v-model="form.subtitle" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                            placeholder="Официальные лицензии Microsoft, Kaspersky..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Текст кнопки</label>
                            <input v-model="form.button_text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="Перейти в каталог" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Ссылка кнопки</label>
                            <input v-model="form.button_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" placeholder="/catalog" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Порядок сортировки</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" v-model="form.is_active" class="rounded" /> Активен (отображается на сайте)
                    </label>

                    <div v-if="errors.general" class="text-red-500 text-sm">{{ errors.general[0] }}</div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 disabled:opacity-60 transition-colors">
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                        <button type="button" @click="showModal = false"
                            class="flex-1 border border-gray-300 text-muted py-2.5 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
