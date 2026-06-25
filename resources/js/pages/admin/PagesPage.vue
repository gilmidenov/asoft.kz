<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// ── Список разделов ─────────────────────────────────────────────
const pages        = ref([])
const loadingPages = ref(true)

// ── Выбранный раздел (редактирование элементов) ─────────────────
const selectedPage = ref(null)   // { id, title, slug }
const items        = ref([])
const loadingItems = ref(false)

// ── Модальные окна ──────────────────────────────────────────────
const showPageModal = ref(false)
const showItemModal = ref(false)
const editPageMode  = ref(false)
const editItemMode  = ref(false)
const saving        = ref(false)
const errors        = ref({})
const editingPageId = ref(null)
const editingItemId = ref(null)

const fileRef       = ref(null)
const filePreview   = ref(null)
const currentFile   = ref(null)
const selectedFile  = ref(null)

const coverRef     = ref(null)
const coverFile    = ref(null)
const coverPreview = ref(null)

// ── Формы ────────────────────────────────────────────────────────
const emptyPageForm = () => ({ title: '', description: '', type: 'catalog', body: '', sort_order: 0, is_active: true })
const emptyItemForm = () => ({ title: '', content: '', file_type: 'text', sort_order: 0, is_active: true })
const pageForm = ref(emptyPageForm())
const itemForm = ref(emptyItemForm())

// ── Загрузка разделов ────────────────────────────────────────────
async function loadPages() {
    loadingPages.value = true
    try {
        const { data } = await axios.get('/admin/pages')
        pages.value = data
    } finally {
        loadingPages.value = false
    }
}

onMounted(loadPages)

// ── Выбор раздела для работы с элементами ────────────────────────
async function selectPage(page) {
    selectedPage.value = page
    loadingItems.value = true
    items.value        = []
    try {
        const { data } = await axios.get(`/admin/pages/${page.id}/items`)
        items.value = data
    } finally {
        loadingItems.value = false
    }
}

function backToPages() {
    selectedPage.value = null
    items.value        = []
}

// ── CRUD разделов ────────────────────────────────────────────────
function openCreatePage() {
    const maxSort = pages.value.length
        ? Math.max(...pages.value.map(p => p.sort_order || 0))
        : 0
    editPageMode.value  = false
    editingPageId.value = null
    pageForm.value      = { ...emptyPageForm(), sort_order: maxSort + 1 }
    errors.value        = {}
    coverFile.value     = null
    coverPreview.value  = null
    showPageModal.value = true
}

function openEditPage(page) {
    editPageMode.value  = true
    editingPageId.value = page.id
    pageForm.value      = {
        title:       page.title,
        description: page.description || '',
        type:        page.type || 'catalog',
        body:        page.body || '',
        sort_order:  page.sort_order || 0,
        is_active:   !!page.is_active,
    }
    errors.value       = {}
    coverFile.value    = null
    coverPreview.value = page.cover_image || null
    showPageModal.value = true
}

async function savePage() {
    saving.value = true
    errors.value = {}
    try {
        let savedId = editingPageId.value

        if (editPageMode.value) {
            await axios.put(`/admin/pages/${savedId}`, pageForm.value)
        } else {
            const { data } = await axios.post('/admin/pages', pageForm.value)
            savedId = data.id
        }

        // Загружаем обложку для раздела-типа "section"
        if (coverFile.value) {
            const fd = new FormData()
            fd.append('image', coverFile.value)
            await axios.post(`/admin/pages/${savedId}/cover`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showPageModal.value = false
        await loadPages()

        // Для нового каталог-типа — сразу открываем управление контентом
        if (!editPageMode.value && pageForm.value.type === 'catalog') {
            const newPage = pages.value.find(p => p.id === savedId)
            if (newPage) await selectPage(newPage)
        }
    } catch (e) {
        errors.value = e.response?.data?.errors || { general: [e.response?.data?.message || 'Ошибка'] }
    } finally {
        saving.value = false
    }
}

async function removePage(id) {
    if (!confirm('Удалить раздел? Все его элементы тоже будут удалены.')) return
    await axios.delete(`/admin/pages/${id}`)
    await loadPages()
}

// ── CRUD элементов ───────────────────────────────────────────────
function resetFile() {
    selectedFile.value = null
    filePreview.value  = null
    currentFile.value  = null
    if (fileRef.value) fileRef.value.value = ''
}

function openCreateItem() {
    editItemMode.value  = false
    editingItemId.value = null
    itemForm.value      = emptyItemForm()
    errors.value        = {}
    resetFile()
    showItemModal.value = true
}

function openEditItem(item) {
    editItemMode.value  = true
    editingItemId.value = item.id
    itemForm.value = {
        title:      item.title,
        content:    item.content || '',
        file_type:  item.file_type || 'text',
        sort_order: item.sort_order || 0,
        is_active:  !!item.is_active,
    }
    errors.value      = {}
    resetFile()
    currentFile.value = item.file_path || null
    showItemModal.value = true
}

function onFilePicked(e) {
    const file = e.target.files[0]
    if (!file) return
    selectedFile.value = file
    if (file.type.startsWith('image/')) {
        filePreview.value = URL.createObjectURL(file)
        itemForm.value.file_type = 'image'
    } else if (file.type === 'application/pdf') {
        filePreview.value = null
        itemForm.value.file_type = 'pdf'
    }
}

async function saveItem() {
    saving.value = true
    errors.value = {}
    try {
        let savedId = editingItemId.value

        if (editItemMode.value) {
            await axios.put(`/admin/items/${savedId}`, itemForm.value)
        } else {
            const { data } = await axios.post(`/admin/pages/${selectedPage.value.id}/items`, itemForm.value)
            savedId = data.id
        }

        if (selectedFile.value) {
            const fd = new FormData()
            fd.append('file', selectedFile.value)
            await axios.post(`/admin/items/${savedId}/file`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showItemModal.value = false
        await selectPage(selectedPage.value)
    } catch (e) {
        errors.value = e.response?.data?.errors || { general: [e.response?.data?.message || 'Ошибка'] }
    } finally {
        saving.value = false
    }
}

async function removeItem(id) {
    if (!confirm('Удалить элемент?')) return
    await axios.delete(`/admin/items/${id}`)
    await selectPage(selectedPage.value)
}

function onCoverPicked(e) {
    const file = e.target.files[0]
    if (!file) return
    coverFile.value    = file
    coverPreview.value = URL.createObjectURL(file)
}

// Метка типа файла
const fileTypeLabel = { image: 'Изображение', pdf: 'PDF', text: 'Статья' }
</script>

<template>
    <div class="p-8">

        <!-- ── Список разделов ─────────────────────────────────── -->
        <template v-if="!selectedPage">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-dark">Разделы компании</h1>
                <button @click="openCreatePage" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                    + Добавить раздел
                </button>
            </div>
            <p class="text-muted text-sm mb-6">Разделы отображаются в третьей строке шапки сайта. Нажмите «Контент», чтобы управлять статьями, документами и изображениями внутри раздела.</p>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div v-if="loadingPages" class="p-8 text-center text-muted">Загрузка...</div>
                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Тип / Контент</th>
                            <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Порядок</th>
                            <th class="px-6 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-dark">{{ page.title }}</td>
                            <td class="px-6 py-4 text-muted font-mono text-xs">{{ page.slug }}</td>
                            <td class="px-6 py-4">
                                <span v-if="page.type === 'section'" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Раздел</span>
                                <span v-else class="text-xs text-muted">{{ page.all_items_count ?? 0 }} эл.</span>
                            </td>
                            <td class="px-6 py-4 text-muted">{{ page.sort_order }}</td>
                            <td class="px-6 py-4">
                                <span :class="page.is_active ? 'text-green-600' : 'text-gray-400'" class="font-medium text-xs">
                                    {{ page.is_active ? 'Активен' : 'Скрыт' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button v-if="page.type !== 'section'" @click="selectPage(page)" class="bg-primary text-white text-xs px-3 py-1.5 rounded-lg hover:bg-primary-700 transition-colors font-medium">
                                    Контент
                                </button>
                                <button @click="openEditPage(page)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                                <button @click="removePage(page.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                            </td>
                        </tr>
                        <tr v-if="pages.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-muted">Разделов нет</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- ── Элементы выбранного раздела ────────────────────── -->
        <template v-else>
            <div class="flex items-center gap-4 mb-6">
                <button @click="backToPages" class="text-muted hover:text-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div>
                    <p class="text-xs text-muted">Разделы компании</p>
                    <h1 class="text-2xl font-bold text-dark">{{ selectedPage.title }}</h1>
                </div>
                <button @click="openCreateItem" class="ml-auto bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                    + Добавить элемент
                </button>
            </div>
            <p class="text-muted text-sm mb-6">Элементы отображаются на странице раздела в стиле мини-каталога. Можно добавлять статьи, изображения и PDF документы.</p>

            <div v-if="loadingItems" class="p-8 text-center text-muted">Загрузка...</div>

            <div v-else-if="items.length === 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <p class="text-muted">Элементов пока нет. Добавьте первый!</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="item in items" :key="item.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                    <!-- Превью -->
                    <div class="aspect-video bg-gray-100 overflow-hidden">
                        <img v-if="item.file_type === 'image' && item.file_path" :src="item.file_path" class="w-full h-full object-cover" />
                        <div v-else-if="item.file_type === 'pdf'" class="w-full h-full flex items-center justify-center bg-red-50">
                            <svg class="w-12 h-12 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/>
                            </svg>
                        </div>
                        <div v-else class="w-full h-full flex items-center justify-center bg-primary-50">
                            <svg class="w-10 h-10 text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-dark text-sm leading-snug line-clamp-2">{{ item.title }}</p>
                            <span :class="{
                                'bg-blue-100 text-blue-700':  item.file_type === 'image',
                                'bg-red-100 text-red-700':    item.file_type === 'pdf',
                                'bg-gray-100 text-gray-600':  item.file_type === 'text',
                            }" class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0">
                                {{ fileTypeLabel[item.file_type] }}
                            </span>
                        </div>
                        <p v-if="item.content" class="text-muted text-xs mt-1 line-clamp-2">{{ item.content }}</p>

                        <div class="flex gap-2 mt-3">
                            <button @click="openEditItem(item)" class="text-xs text-primary hover:underline font-medium">Изменить</button>
                            <button @click="removeItem(item.id)" class="text-xs text-red-500 hover:underline font-medium">Удалить</button>
                            <span :class="item.is_active ? 'text-green-500' : 'text-gray-300'" class="text-xs ml-auto">
                                {{ item.is_active ? '● Активен' : '● Скрыт' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Модал: создание/редактирование раздела ──────────── -->
        <div v-if="showPageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-dark">{{ editPageMode ? 'Редактировать раздел' : 'Новый раздел' }}</h2>
                    <button @click="showPageModal = false" class="text-muted hover:text-dark text-xl">&times;</button>
                </div>
                <form @submit.prevent="savePage" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <!-- Название -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">
                            Название *
                            <span :class="pageForm.title.length > 23 ? 'text-accent' : 'text-gray-400'" class="ml-2 text-xs font-normal">
                                {{ pageForm.title.length }}/30
                            </span>
                        </label>
                        <input
                            v-model="pageForm.title"
                            required
                            maxlength="30"
                            placeholder="До 30 символов — отображается в шапке сайта"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                        />
                        <p class="text-xs text-muted mt-1">Название видно в навигационной полосе — чем короче, тем лучше.</p>
                        <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                    </div>

                    <!-- Тип раздела -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Тип раздела</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="pageForm.type" value="catalog" class="text-primary" />
                                <span class="text-sm font-medium">Мини-каталог</span>
                                <span class="text-xs text-muted hidden sm:inline">(статьи, PDF, изображения)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="pageForm.type" value="section" class="text-primary" />
                                <span class="text-sm font-medium">Раздел</span>
                                <span class="text-xs text-muted hidden sm:inline">(текст + обложка)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Описание -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Краткое описание</label>
                        <textarea v-model="pageForm.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"></textarea>
                    </div>

                    <!-- Текст раздела (только для section) -->
                    <div v-if="pageForm.type === 'section'">
                        <label class="block text-sm font-medium text-dark mb-1">Текст страницы</label>
                        <textarea
                            v-model="pageForm.body"
                            rows="10"
                            placeholder="Введите основной текст раздела. Например: история компании, описание услуг и т.д."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary resize-y"
                        ></textarea>
                    </div>

                    <!-- Обложка (только для section) -->
                    <div v-if="pageForm.type === 'section'">
                        <label class="block text-sm font-medium text-dark mb-2">Изображение обложки</label>
                        <img v-if="coverPreview" :src="coverPreview" class="w-full h-36 object-cover rounded-lg border border-gray-200 mb-2" />
                        <label class="flex items-center justify-center w-full h-12 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors bg-gray-50">
                            <span class="text-xs text-muted">{{ coverPreview ? 'Заменить изображение' : 'Загрузить обложку (JPG, PNG, WebP)' }}</span>
                            <input ref="coverRef" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onCoverPicked" />
                        </label>
                    </div>

                    <!-- Порядок и статус -->
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-dark mb-1">Порядок сортировки</label>
                            <input v-model.number="pageForm.sort_order" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        </div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer pb-2">
                            <input type="checkbox" v-model="pageForm.is_active" class="rounded" /> Активен
                        </label>
                    </div>

                    <div v-if="errors.general" class="text-red-500 text-sm">{{ errors.general[0] }}</div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 disabled:opacity-60 transition-colors">
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                        <button type="button" @click="showPageModal = false"
                            class="flex-1 border border-gray-300 text-muted py-2.5 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Модал: создание/редактирование элемента ─────────── -->
        <div v-if="showItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                    <h2 class="font-bold text-dark">{{ editItemMode ? 'Редактировать элемент' : 'Новый элемент' }}</h2>
                    <button @click="showItemModal = false" class="text-muted hover:text-dark text-xl">&times;</button>
                </div>
                <form @submit.prevent="saveItem" class="p-6 space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Название *</label>
                        <input v-model="itemForm.title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark mb-1">Текст / описание</label>
                        <textarea v-model="itemForm.content" rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                            placeholder="Краткое описание, аннотация статьи..."></textarea>
                    </div>

                    <!-- Файл (изображение или PDF) -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Файл (изображение или PDF)</label>

                        <!-- Текущий файл -->
                        <div v-if="currentFile && !filePreview" class="mb-3">
                            <img v-if="itemForm.file_type === 'image'" :src="currentFile" class="w-32 h-20 object-cover rounded-lg border border-gray-200" />
                            <div v-else class="flex items-center gap-2 text-xs text-muted bg-red-50 border border-red-100 rounded-lg p-3">
                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/>
                                </svg>
                                Текущий PDF документ
                            </div>
                        </div>

                        <!-- Предпросмотр нового изображения -->
                        <div v-if="filePreview" class="mb-3 flex items-start gap-3">
                            <img :src="filePreview" class="w-32 h-20 object-cover rounded-lg border border-gray-200" />
                            <button type="button" @click="() => { selectedFile = null; filePreview = null }" class="text-xs text-red-500 hover:underline mt-1">Отменить</button>
                        </div>
                        <div v-else-if="selectedFile && itemForm.file_type === 'pdf'" class="mb-3 flex items-center gap-2 text-xs text-muted bg-red-50 border border-red-100 rounded-lg p-3">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/>
                            </svg>
                            {{ selectedFile.name }}
                        </div>

                        <label class="flex items-center justify-center w-full h-14 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors bg-gray-50">
                            <span class="text-xs text-muted">Нажмите — JPG, PNG, WebP или PDF (до 20 МБ)</span>
                            <input ref="fileRef" type="file" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="hidden" @change="onFilePicked" />
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Порядок</label>
                            <input v-model.number="itemForm.sort_order" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" v-model="itemForm.is_active" class="rounded" /> Активен
                            </label>
                        </div>
                    </div>

                    <div v-if="errors.general" class="text-red-500 text-sm">{{ errors.general[0] }}</div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 disabled:opacity-60 transition-colors">
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                        <button type="button" @click="showItemModal = false"
                            class="flex-1 border border-gray-300 text-muted py-2.5 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>
