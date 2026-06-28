<script setup>
import { ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import axios from 'axios'

const route   = useRoute()
const page    = ref(null)
const loading = ref(false)
const error   = ref(false)

// ── Детальный просмотр элемента ─────────────────────────────────
const detail      = ref(null)   // выбранный item
const openDetail  = (item) => { detail.value = item }
const closeDetail = () => { detail.value = null }

async function loadPage(slug) {
    loading.value = true
    error.value   = false
    page.value    = null
    try {
        const { data } = await axios.get(`/pages/${slug}`)
        page.value = data
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

watch(() => route.params.slug, (slug) => { if (slug) loadPage(slug) }, { immediate: true })
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Загрузка -->
        <div v-if="loading" class="flex items-center justify-center py-32">
            <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
        </div>

        <!-- Ошибка / раздел не найден -->
        <div v-else-if="error" class="container mx-auto px-4 py-32 text-center">
            <p class="text-2xl font-bold text-dark mb-3">Раздел не найден</p>
            <p class="text-muted">Запрошенная страница не существует или была удалена.</p>
        </div>

        <template v-else-if="page">
            <!-- Шапка раздела -->
            <div class="bg-header text-white py-12">
                <div class="container mx-auto px-4">
                    <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ page.title }}</h1>
                    <p v-if="page.description" class="text-gray-300 max-w-2xl">{{ page.description }}</p>
                </div>
            </div>

            <!-- ── Тип «Раздел»: обложка + текст ────────────────────── -->
            <div v-if="page.type === 'section'" class="container mx-auto px-4 py-10">
                <div class="max-w-3xl mx-auto">
                    <img v-if="page.cover_image" :src="page.cover_image" :alt="page.title"
                        class="w-full max-h-80 object-cover rounded-2xl mb-8 shadow-sm" />
                    <div v-if="page.body"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-dark text-base leading-7 whitespace-pre-wrap">{{ page.body }}</div>
                    <div v-else-if="!page.cover_image" class="text-center py-20 text-muted">
                        <p class="text-lg font-medium">Раздел пока пуст</p>
                    </div>
                </div>
            </div>

            <!-- ── Раздел «Разработка»: хаб подразделов ─────────────── -->
            <div v-else-if="page.slug === 'development'" class="container mx-auto px-4 py-12">
                <h2 class="text-xl font-bold text-dark mb-2">Направления разработки</h2>
                <p class="text-muted text-sm mb-8">Выберите направление, чтобы узнать подробнее об услугах</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                    <!-- Веб-разработка -->
                    <RouterLink to="/company/veb-razrabotka"
                        class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-primary/30 transition-all group flex flex-col">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-dark mb-2 group-hover:text-primary transition-colors">Веб-разработка</h3>
                        <p class="text-muted text-sm leading-relaxed flex-1">
                            Корпоративные сайты, интернет-магазины, лендинги и веб-сервисы любой сложности
                        </p>
                        <div class="mt-4 flex items-center gap-1.5 text-primary text-sm font-medium">
                            <span>Подробнее</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </RouterLink>

                    <!-- Мобильная разработка (coming soon) -->
                    <div class="bg-white rounded-2xl p-6 border border-dashed border-gray-200 flex flex-col opacity-60 select-none">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-500 mb-2">Мобильная разработка</h3>
                        <p class="text-gray-400 text-sm leading-relaxed flex-1">iOS и Android приложения</p>
                        <span class="mt-4 text-xs text-gray-400 font-medium bg-gray-100 px-3 py-1 rounded-full self-start">Скоро</span>
                    </div>

                    <!-- CRM/ERP (coming soon) -->
                    <div class="bg-white rounded-2xl p-6 border border-dashed border-gray-200 flex flex-col opacity-60 select-none">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-500 mb-2">CRM и ERP системы</h3>
                        <p class="text-gray-400 text-sm leading-relaxed flex-1">Автоматизация бизнес-процессов</p>
                        <span class="mt-4 text-xs text-gray-400 font-medium bg-gray-100 px-3 py-1 rounded-full self-start">Скоро</span>
                    </div>

                </div>
            </div>

            <!-- ── Тип «Мини-каталог»: сетка элементов ──────────────── -->
            <div v-else class="container mx-auto px-4 py-10">

                <!-- Нет элементов -->
                <div v-if="!page.items || page.items.length === 0" class="text-center py-20 text-muted">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">Раздел пока пуст</p>
                    <p class="text-sm mt-1">Контент будет добавлен в ближайшее время.</p>
                </div>

                <!-- Сетка карточек — все кликабельны -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    <div
                        v-for="item in page.items"
                        :key="item.id"
                        class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md hover:border-primary/30 transition-all group cursor-pointer"
                        @click="openDetail(item)"
                    >
                        <!-- Превью изображения -->
                        <div v-if="item.file_type === 'image' && item.file_path"
                            class="aspect-video overflow-hidden bg-gray-100">
                            <img :src="item.file_path" :alt="item.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        </div>

                        <!-- PDF иконка -->
                        <div v-else-if="item.file_type === 'pdf'"
                            class="aspect-video flex items-center justify-center bg-red-50">
                            <svg class="w-16 h-16 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9.5 17v-1h5v1h-5zm0-3v-1h5v1h-5zm0-3V10h5v1h-5z"/>
                            </svg>
                        </div>

                        <!-- Текстовая карточка (нет файла) -->
                        <div v-else class="aspect-video flex flex-col items-center justify-center bg-primary-50 px-4 gap-2">
                            <svg class="w-8 h-8 text-primary-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-primary font-semibold text-center text-xs line-clamp-2">{{ item.title }}</p>
                        </div>

                        <!-- Текст карточки -->
                        <div class="p-4">
                            <h3 class="font-semibold text-dark text-sm leading-snug mb-1 line-clamp-2 group-hover:text-primary transition-colors">
                                {{ item.title }}
                            </h3>
                            <p v-if="item.content" class="text-muted text-xs leading-relaxed line-clamp-2">
                                {{ item.content }}
                            </p>

                            <div class="mt-3 flex items-center justify-between">
                                <span :class="{
                                    'bg-blue-100 text-blue-700':  item.file_type === 'image',
                                    'bg-red-100 text-red-700':    item.file_type === 'pdf',
                                    'bg-gray-100 text-gray-600':  item.file_type === 'text',
                                }" class="text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ item.file_type === 'image' ? 'Изображение' : item.file_type === 'pdf' ? 'PDF' : 'Статья' }}
                                </span>
                                <span class="text-xs text-primary font-medium">Открыть →</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Модал детального просмотра элемента ────────────────── -->
        <Teleport to="body">
            <div v-if="detail" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4" @click.self="closeDetail">
                <div class="relative w-full max-w-3xl max-h-[90vh] bg-white rounded-2xl overflow-hidden flex flex-col shadow-2xl">

                    <!-- Шапка -->
                    <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                        <div class="flex-1 pr-4">
                            <span :class="{
                                'bg-blue-100 text-blue-700':  detail.file_type === 'image',
                                'bg-red-100 text-red-700':    detail.file_type === 'pdf',
                                'bg-gray-100 text-gray-600':  detail.file_type === 'text',
                            }" class="text-xs font-medium px-2 py-0.5 rounded-full mb-2 inline-block">
                                {{ detail.file_type === 'image' ? 'Изображение' : detail.file_type === 'pdf' ? 'PDF документ' : 'Статья' }}
                            </span>
                            <h3 class="font-bold text-dark text-lg leading-snug">{{ detail.title }}</h3>
                        </div>
                        <button @click="closeDetail" class="text-muted hover:text-dark text-2xl leading-none flex-shrink-0">&times;</button>
                    </div>

                    <!-- Контент с прокруткой -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- Изображение -->
                        <img v-if="detail.file_type === 'image' && detail.file_path"
                            :src="detail.file_path" :alt="detail.title"
                            class="w-full object-cover max-h-72 bg-gray-50" />

                        <!-- PDF iframe -->
                        <iframe v-else-if="detail.file_type === 'pdf' && detail.file_path"
                            :src="detail.file_path"
                            class="w-full" style="height: 55vh;" />

                        <!-- Нет файла — иконка статьи -->
                        <div v-else class="flex items-center justify-center bg-primary-50 py-8">
                            <svg class="w-14 h-14 text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>

                        <!-- Текст: краткое + полное описание -->
                        <div class="px-6 py-5 space-y-4">
                            <p v-if="detail.content" class="text-muted text-sm leading-relaxed">{{ detail.content }}</p>
                            <div v-if="detail.body"
                                :class="detail.content ? 'border-t border-gray-100 pt-4' : ''"
                                class="text-dark text-sm leading-7 whitespace-pre-wrap">{{ detail.body }}</div>
                        </div>
                    </div>

                    <!-- Подвал с кнопкой скачать для PDF -->
                    <div v-if="detail.file_type === 'pdf' && detail.file_path"
                        class="px-6 py-3 border-t border-gray-100 flex justify-end bg-gray-50">
                        <a :href="detail.file_path" target="_blank" download
                            class="text-sm text-primary font-medium hover:underline">
                            Скачать PDF
                        </a>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
