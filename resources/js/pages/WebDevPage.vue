<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'

const portfolio = ref([])
const loading   = ref(true)
const detail    = ref(null)

const openDetail  = (item) => { detail.value = item }
const closeDetail = () => { detail.value = null }

onMounted(async () => {
    try {
        const { data } = await axios.get('/pages/veb-razrabotka')
        portfolio.value = data.items || []
    } catch {
        // portfolio unavailable
    } finally {
        loading.value = false
    }
})

const services = [
    {
        title: 'Корпоративный сайт',
        desc: 'Представительский сайт компании с современным дизайном и удобной системой управления контентом',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>`,
    },
    {
        title: 'Интернет-магазин',
        desc: 'Полнофункциональный e-commerce с каталогом, корзиной, личным кабинетом и приёмом платежей',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>`,
    },
    {
        title: 'Лендинг',
        desc: 'Продающая одностраничная страница с высокой конверсией для продукта, услуги или мероприятия',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>`,
    },
    {
        title: 'CRM и ERP системы',
        desc: 'Индивидуальные системы автоматизации бизнес-процессов, управления клиентами и задачами',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>`,
    },
    {
        title: 'Интеграции и API',
        desc: 'Подключаем платёжные системы, 1С, маркетплейсы, SMS-шлюзы и внешние сервисы',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>`,
    },
    {
        title: 'Поддержка и SEO',
        desc: 'Техническая поддержка, обновления, резервное копирование, SEO-оптимизация и продвижение',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>`,
    },
]

const steps = [
    { num: '01', title: 'Знакомство и бриф', desc: 'Обсуждаем цели, целевую аудиторию, функционал и бюджет проекта' },
    { num: '02', title: 'Дизайн и прототип', desc: 'Разрабатываем UI/UX макет и согласовываем его с вами до начала кодинга' },
    { num: '03', title: 'Разработка', desc: 'Верстаем и программируем сайт с тестированием на каждом этапе' },
    { num: '04', title: 'Запуск', desc: 'Размещаем на хостинге, настраиваем домен, SSL и передаём вам доступы' },
]

const techs = [
    'Vue.js', 'React', 'Laravel', 'Node.js',
    'PostgreSQL', 'MySQL', 'Docker', 'Nginx',
    'Redis', 'TypeScript', 'Tailwind CSS', 'REST API',
]

const stats = [
    { value: '10+',    label: 'лет опыта' },
    { value: '200+',   label: 'проектов' },
    { value: '95%',    label: 'довольных клиентов' },
    { value: '12 мес', label: 'гарантия' },
]
</script>

<template>
    <!-- ─── Hero ────────────────────────────────────────────────── -->
    <section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 text-white overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
        <div class="absolute -bottom-16 -left-16 w-72 h-72 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none" />

        <div class="relative container mx-auto px-4 py-20 lg:py-28">
            <!-- breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
                <RouterLink to="/company/development" class="hover:text-white transition-colors">Разработка</RouterLink>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-200">Веб-разработка</span>
            </div>

            <div class="max-w-3xl">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                    Создаём сайты,<br>
                    <span class="text-primary">которые продают</span>
                </h1>
                <p class="text-gray-300 text-lg md:text-xl leading-relaxed mb-8 max-w-2xl">
                    Разрабатываем корпоративные сайты, интернет-магазины и веб-сервисы для бизнеса
                    в Казахстане. Современный дизайн, быстрая загрузка, SEO-оптимизация.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="mailto:info@asoft.kz"
                        class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-700 text-white font-semibold px-8 py-3.5 rounded-xl transition-colors">
                        Обсудить проект
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <a href="#portfolio"
                        class="inline-flex items-center justify-center gap-2 border border-white/30 text-white hover:bg-white/10 font-semibold px-8 py-3.5 rounded-xl transition-colors">
                        Наши работы
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Stats bar ────────────────────────────────────────────── -->
    <section class="bg-slate-800 text-white py-6 border-t border-slate-700">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-700">
                <div v-for="stat in stats" :key="stat.label" class="text-center px-4 py-2">
                    <div class="text-2xl md:text-3xl font-bold text-primary">{{ stat.value }}</div>
                    <div class="text-gray-400 text-sm mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Services ─────────────────────────────────────────────── -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-dark">Наши услуги</h2>
                <p class="text-muted mt-3 max-w-xl mx-auto">
                    Полный цикл веб-разработки — от идеи до запуска и дальнейшей поддержки
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="svc in services"
                    :key="svc.title"
                    class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-primary/30 transition-all group"
                >
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            v-html="svc.icon" />
                    </div>
                    <h3 class="text-base font-bold text-dark mb-2 group-hover:text-primary transition-colors">
                        {{ svc.title }}
                    </h3>
                    <p class="text-muted text-sm leading-relaxed">{{ svc.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Process ──────────────────────────────────────────────── -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-dark">Как мы работаем</h2>
                <p class="text-muted mt-3">Прозрачный процесс на каждом этапе проекта</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(step, i) in steps"
                    :key="step.num"
                    class="relative bg-primary/5 rounded-2xl p-6 border border-primary/10"
                >
                    <div class="text-5xl font-black text-primary/15 leading-none mb-4 select-none">{{ step.num }}</div>
                    <h3 class="text-base font-bold text-dark mb-2">{{ step.title }}</h3>
                    <p class="text-muted text-sm leading-relaxed">{{ step.desc }}</p>
                    <!-- arrow connector -->
                    <div v-if="i < steps.length - 1"
                        class="hidden lg:flex absolute -right-5 top-1/2 -translate-y-1/2 z-10 w-10 h-10 items-center justify-center">
                        <svg class="w-5 h-5 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Technologies ─────────────────────────────────────────── -->
    <section class="py-14 bg-slate-900 text-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-bold">Технологический стек</h2>
                <p class="text-gray-400 mt-2 text-sm">Используем проверенные и современные технологии</p>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                <span
                    v-for="tech in techs"
                    :key="tech"
                    class="bg-slate-800 border border-slate-700 text-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:border-primary hover:text-primary transition-colors"
                >
                    {{ tech }}
                </span>
            </div>
        </div>
    </section>

    <!-- ─── Portfolio ────────────────────────────────────────────── -->
    <section id="portfolio" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-dark">Портфолио</h2>
                <p class="text-muted mt-3">Примеры наших реализованных проектов</p>
            </div>

            <div v-if="loading" class="flex justify-center py-16">
                <div class="w-9 h-9 border-4 border-primary border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="portfolio.length === 0" class="text-center py-20 text-muted">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-lg font-medium">Портфолио скоро появится</p>
                <p class="text-sm mt-1">Примеры работ в процессе подготовки. Свяжитесь с нами, чтобы узнать больше.</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="item in portfolio"
                    :key="item.id"
                    class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-lg hover:border-primary/20 transition-all group cursor-pointer"
                    @click="openDetail(item)"
                >
                    <div class="aspect-video bg-gray-100 overflow-hidden">
                        <img
                            v-if="item.file_type === 'image' && item.file_path"
                            :src="item.file_path"
                            :alt="item.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary/10 to-emerald-100">
                            <svg class="w-12 h-12 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-dark mb-1.5 group-hover:text-primary transition-colors leading-snug">
                            {{ item.title }}
                        </h3>
                        <p v-if="item.content" class="text-muted text-sm leading-relaxed line-clamp-2">
                            {{ item.content }}
                        </p>
                        <div class="mt-4 flex items-center gap-1.5 text-primary text-sm font-medium">
                            <span>Подробнее</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── CTA ──────────────────────────────────────────────────── -->
    <section class="py-16 bg-gradient-to-r from-primary to-emerald-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Готовы начать проект?</h2>
            <p class="text-emerald-100 text-lg mb-8 max-w-xl mx-auto">
                Расскажите нам о вашей задаче — мы предложим решение и рассчитаем стоимость бесплатно
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="mailto:info@asoft.kz"
                    class="inline-flex items-center justify-center gap-2 bg-white text-primary font-bold px-8 py-3.5 rounded-xl hover:bg-emerald-50 transition-colors"
                >
                    Написать нам
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </a>
                <a
                    href="tel:+77075973777"
                    class="inline-flex items-center justify-center gap-2 border-2 border-white/40 text-white hover:bg-white/10 font-semibold px-8 py-3.5 rounded-xl transition-colors"
                >
                    +7 (707) 597-37-77
                </a>
            </div>
        </div>
    </section>

    <!-- ─── Portfolio detail modal ───────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="detail"
            class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4"
            @click.self="closeDetail"
        >
            <div class="relative w-full max-w-3xl max-h-[90vh] bg-white rounded-2xl overflow-hidden flex flex-col shadow-2xl">
                <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-dark text-lg leading-snug pr-4">{{ detail.title }}</h3>
                    <button @click="closeDetail" class="text-muted hover:text-dark text-3xl leading-none flex-shrink-0 -mt-1">&times;</button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <img
                        v-if="detail.file_type === 'image' && detail.file_path"
                        :src="detail.file_path"
                        :alt="detail.title"
                        class="w-full object-contain bg-gray-50"
                    />
                    <div class="px-6 py-5 space-y-3">
                        <p v-if="detail.content" class="text-muted text-sm leading-relaxed">{{ detail.content }}</p>
                        <div v-if="detail.body" class="text-dark text-sm leading-7 whitespace-pre-wrap pt-1">{{ detail.body }}</div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
