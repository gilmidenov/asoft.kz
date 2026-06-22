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

const imageFile        = ref(null)
const imagePreview     = ref(null)
const currentImage     = ref(null)
const uploadingImage   = ref(false)
const imageInputRef    = ref(null)

const licenses = ref([])

const emptyForm = () => ({
    name: '', category_id: '', vendor_id: '', short_description: '',
    description: '', version: '', language: '', delivery_type: 'key',
    status: 'active', is_hit: false, is_new: false, is_sale: false,
    stock_quantity: '', price_from: '',
})

const emptyLicense = () => ({
    name: '', price: '', old_price: '', type: 'perpetual',
    devices: '', duration_months: '', in_stock: true,
})

const form = ref(emptyForm())
const editingId = ref(null)

const deliveryLabels = { download: 'Загрузка', box: 'Коробка', key: 'Ключ' }
const licenseTypes   = { perpetual: 'Бессрочная', subscription: 'Подписка', volume: 'Корпоративная' }
const statusLabels   = {
    active:       { label: 'Активен',         color: 'text-green-600' },
    inactive:     { label: 'Неактивен',        color: 'text-gray-400' },
    out_of_stock: { label: 'Нет в наличии',    color: 'text-red-500'  },
}

function imageUrl(path) {
    if (!path) return null
    if (path.startsWith('http')) return path
    return '/storage/' + path
}

async function load() {
    loading.value = true
    try {
        const { data } = await axios.get('/admin/products', { params: { page: page.value, per_page: 20 } })
        products.value   = data.data
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
    licenses.value  = []
    errors.value    = {}
    resetImageState()
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
        stock_quantity:    product.stock_quantity ?? '',
        price_from:        product.price_from ?? '',
    }
    licenses.value = (product.licenses || []).map(l => ({
        name:            l.name,
        price:           l.price,
        old_price:       l.old_price ?? '',
        type:            l.type || 'perpetual',
        devices:         l.devices || '',
        duration_months: l.duration_months ?? '',
        in_stock:        !!l.in_stock,
    }))
    errors.value       = {}
    imageFile.value    = null
    imagePreview.value = null
    currentImage.value = product.main_image || null
    if (imageInputRef.value) imageInputRef.value.value = ''
    showModal.value    = true
}

function addLicense() {
    licenses.value.push(emptyLicense())
}

function removeLicense(i) {
    licenses.value.splice(i, 1)
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

async function removeCurrentImage() {
    if (!editingId.value || !currentImage.value) return
    if (!confirm('Удалить текущее изображение?')) return
    await axios.delete(`/admin/products/${editingId.value}/image`)
    currentImage.value = null
    await load()
}

async function save() {
    saving.value = true
    errors.value = {}
    try {
        let savedId = editingId.value

        if (editMode.value) {
            await axios.put(`/admin/products/${savedId}`, form.value)
        } else {
            const { data } = await axios.post('/admin/products', form.value)
            savedId = data.id
        }

        // Синхронизируем лицензии
        await axios.post(`/admin/products/${savedId}/licenses/sync`, {
            licenses: licenses.value.map(l => ({
                ...l,
                price:           Number(l.price) || 0,
                old_price:       l.old_price !== '' ? Number(l.old_price) : null,
                duration_months: l.duration_months !== '' ? Number(l.duration_months) : null,
            })),
        })

        if (imageFile.value) {
            uploadingImage.value = true
            const fd = new FormData()
            fd.append('image', imageFile.value)
            await axios.post(`/admin/products/${savedId}/image`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showModal.value = false
        await load()
    } catch (e) {
        errors.value = e.response?.data?.errors || { general: [e.response?.data?.message || 'Ошибка'] }
    } finally {
        saving.value         = false
        uploadingImage.value = false
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
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase w-12"></th>
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase">Название</th>
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase">Категория</th>
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase">Вендор</th>
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase">Статус</th>
                        <th class="px-4 py-3 text-center text-xs text-muted font-semibold uppercase">Лицензий</th>
                        <th class="px-4 py-3 text-left text-xs text-muted font-semibold uppercase">Метки</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="p in products" :key="p.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <img v-if="p.main_image" :src="imageUrl(p.main_image)" class="w-9 h-9 object-cover rounded-lg border border-gray-200" />
                            <div v-else class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300 text-xs">—</div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ p.name }}</td>
                        <td class="px-4 py-3 text-muted">{{ p.category?.name || '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ p.vendor?.name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span :class="statusLabels[p.status]?.color" class="font-medium text-xs">
                                {{ statusLabels[p.status]?.label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            <span v-if="p.licenses?.length" class="font-medium text-primary">{{ p.licenses.length }}</span>
                            <span v-else class="text-red-400 text-xs font-medium">Нет</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="p.is_hit"  class="inline-block bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded mr-1">Хит</span>
                            <span v-if="p.is_new"  class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded mr-1">Новинка</span>
                            <span v-if="p.is_sale" class="inline-block bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded">Акция</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button @click="openEdit(p)" class="text-primary hover:underline text-xs font-medium">Изменить</button>
                            <button @click="remove(p.id)" class="text-red-500 hover:underline text-xs font-medium">Удалить</button>
                        </td>
                    </tr>
                    <tr v-if="products.length === 0">
                        <td colspan="8" class="px-6 py-10 text-center text-muted">Товаров пока нет</td>
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
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h2 class="font-bold text-dark">{{ editMode ? 'Редактировать товар' : 'Новый товар' }}</h2>
                    <button @click="showModal = false" class="text-muted hover:text-dark text-xl leading-none">&times;</button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-5">

                    <!-- Изображение -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Изображение товара</label>
                        <div v-if="currentImage && !imagePreview" class="mb-3 flex items-start gap-3">
                            <img :src="imageUrl(currentImage)" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
                            <button type="button" @click="removeCurrentImage" class="text-xs text-red-500 hover:underline mt-1">Удалить фото</button>
                        </div>
                        <div v-if="imagePreview" class="mb-3 flex items-start gap-3">
                            <img :src="imagePreview" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
                            <button type="button" @click="clearImagePick" class="text-xs text-red-500 hover:underline mt-1">Отменить выбор</button>
                        </div>
                        <label class="flex items-center justify-center w-full h-20 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors bg-gray-50">
                            <div class="text-center">
                                <p class="text-sm text-muted">{{ imagePreview ? 'Выбрать другое' : 'Нажмите для выбора' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WebP — до 4 МБ</p>
                            </div>
                            <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onImagePicked" />
                        </label>
                        <p v-if="errors.image" class="text-red-500 text-xs mt-1">{{ errors.image[0] }}</p>
                    </div>

                    <!-- Основные поля -->
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
                        <textarea v-model="form.description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"></textarea>
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
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Цена от (₸) — для каталога</label>
                            <input v-model.number="form.price_from" type="number" min="0" placeholder="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark mb-1">Количество на складе</label>
                            <input v-model.number="form.stock_quantity" type="number" min="0" placeholder="Не ограничено"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary" />
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

                    <!-- Лицензии -->
                    <div class="border-t border-gray-100 pt-5">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-dark">Варианты лицензий</h3>
                                <p class="text-xs text-muted mt-0.5">Цены и варианты покупки — отображаются на странице товара</p>
                            </div>
                            <button type="button" @click="addLicense"
                                class="text-xs bg-primary/10 text-primary px-3 py-1.5 rounded-lg hover:bg-primary/20 transition-colors font-medium">
                                + Добавить
                            </button>
                        </div>

                        <div v-if="licenses.length === 0" class="text-center py-6 bg-amber-50 rounded-lg border border-amber-100">
                            <p class="text-sm text-amber-700 font-medium">Нет лицензий — товар нельзя купить</p>
                            <p class="text-xs text-amber-600 mt-1">Добавьте хотя бы один вариант цены</p>
                        </div>

                        <div v-for="(lic, i) in licenses" :key="i"
                            class="mb-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-semibold text-muted uppercase tracking-wide">Лицензия {{ i + 1 }}</span>
                                <button type="button" @click="removeLicense(i)" class="text-red-400 hover:text-red-600 text-xs">✕ Удалить</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div class="col-span-2">
                                    <label class="block text-xs text-muted mb-1">Название *</label>
                                    <input v-model="lic.name" required placeholder="напр. 1 ПК бессрочно"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label class="block text-xs text-muted mb-1">Цена (₸) *</label>
                                    <input v-model="lic.price" type="number" min="0" required placeholder="0"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label class="block text-xs text-muted mb-1">Старая цена (₸)</label>
                                    <input v-model="lic.old_price" type="number" min="0" placeholder="для зачёркивания"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label class="block text-xs text-muted mb-1">Тип</label>
                                    <select v-model="lic.type" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary">
                                        <option v-for="(label, val) in licenseTypes" :key="val" :value="val">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-muted mb-1">Срок (мес.)</label>
                                    <input v-model="lic.duration_months" type="number" min="1" placeholder="пусто = бессрочно"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary" />
                                </div>
                                <div>
                                    <label class="block text-xs text-muted mb-1">Устройства</label>
                                    <input v-model="lic.devices" placeholder="1, 3, 5, unlimited"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary" />
                                </div>
                                <div class="flex items-center gap-2 pt-4">
                                    <input type="checkbox" v-model="lic.in_stock" :id="`in_stock_${i}`" class="rounded" />
                                    <label :for="`in_stock_${i}`" class="text-sm cursor-pointer">В наличии</label>
                                </div>
                            </div>
                        </div>
                        <p v-if="errors['licenses.0.name'] || errors['licenses.0.price']" class="text-red-500 text-xs mt-1">
                            Заполните обязательные поля лицензий
                        </p>
                    </div>

                    <div v-if="errors.general" class="text-red-500 text-sm">{{ errors.general[0] }}</div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="saving" class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 disabled:opacity-60 transition-colors">
                            {{ uploadingImage ? 'Загрузка фото...' : saving ? 'Сохранение...' : 'Сохранить' }}
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
