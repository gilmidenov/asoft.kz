<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCatalogStore } from '@/stores/catalog'
import axios from 'axios'

const authStore    = useAuthStore()
const cartStore    = useCartStore()
const catalogStore = useCatalogStore()
const router       = useRouter()

const searchQuery     = ref('')
const catalogMenuOpen = ref(false)
const hoveredCat      = ref(null)
const companyPages    = ref([])

// ── Карусель разделов компании ────────────────────────────────────
const navRef   = ref(null)   // ref на прокручиваемый контейнер
const showPrev = ref(false)  // нужна ли стрелка «назад»
const showNext = ref(false)  // нужна ли стрелка «вперёд»

// Пересчитываем видимость стрелок при прокрутке
function updateArrows() {
    const el = navRef.value
    if (!el) return
    showPrev.value = el.scrollLeft > 2
    showNext.value = el.scrollLeft < el.scrollWidth - el.clientWidth - 2
}

// Прокручиваем карусель на amount пикселей (плавно)
function scrollNav(amount) {
    navRef.value?.scrollBy({ left: amount, behavior: 'smooth' })
    // Обновляем стрелки после завершения анимации прокрутки (300 мс)
    setTimeout(updateArrows, 350)
}

// После загрузки разделов проверяем, нужна ли стрелка вперёд
watch(companyPages, async () => {
    await nextTick()
    updateArrows()
})

onMounted(async () => {
    catalogStore.fetchCategories()
    try {
        const { data } = await axios.get('/pages')
        companyPages.value = data
    } catch {
        // навигация по разделам не критична
    }
})

function handleSearch() {
    if (searchQuery.value.trim()) {
        router.push({ name: 'catalog', query: { search: searchQuery.value } })
    }
}
</script>

<template>
    <header class="sticky top-0 z-50 shadow-md">

        <!-- Верхняя тёмная полоса: лого / телефон / корзина+кабинет -->
        <div class="bg-header text-white">
            <div class="container mx-auto px-4 py-2 flex items-center justify-between text-sm">

                <RouterLink to="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center font-bold text-white text-lg">A</div>
                    <div>
                        <span class="font-bold text-white text-lg tracking-tight">Atlas</span>
                        <span class="text-primary font-bold text-lg"> Software</span>
                    </div>
                </RouterLink>

                <a href="tel:+77075973777" class="text-gray-300 hover:text-white transition-colors">
                    +7 (707) 597-37-77
                </a>

                <div class="flex items-center gap-4">
                    <RouterLink to="/cart" class="flex items-center gap-1 text-gray-300 hover:text-white transition-colors relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Корзина
                        <span v-if="cartStore.count > 0"
                            class="absolute -top-2 -right-3 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                            {{ cartStore.count }}
                        </span>
                    </RouterLink>

                    <RouterLink to="/favorites" class="text-gray-300 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </RouterLink>

                    <template v-if="authStore.isAuthenticated">
                        <RouterLink v-if="authStore.isAdmin" to="/admin" class="text-yellow-400 hover:text-yellow-300 transition-colors text-sm font-medium">
                            Админ
                        </RouterLink>
                        <RouterLink to="/account" class="text-gray-300 hover:text-white transition-colors text-sm">
                            {{ authStore.user?.name }}
                        </RouterLink>
                        <button @click="authStore.logout()" class="text-gray-400 hover:text-white text-sm transition-colors">
                            Выйти
                        </button>
                    </template>
                    <template v-else>
                        <RouterLink to="/login" class="text-gray-300 hover:text-white transition-colors">Войти</RouterLink>
                    </template>
                </div>
            </div>
        </div>

        <!-- Средняя белая полоса: каталог / поиск / быстрые ссылки -->
        <div class="bg-white border-b border-gray-200">
            <div class="container mx-auto px-4 py-3 flex items-center gap-6">

                <div class="relative">
                    <button @click="catalogMenuOpen = !catalogMenuOpen"
                        class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Каталог
                    </button>

                    <div v-if="catalogMenuOpen"
                        class="absolute top-full left-0 mt-1 w-64 bg-white shadow-xl rounded-lg border border-gray-100 py-2 z-50 max-h-[80vh] overflow-y-auto">
                        <div
                            v-for="cat in catalogStore.categories"
                            :key="cat.id"
                            class="relative"
                            @mouseenter="hoveredCat = cat.id"
                            @mouseleave="hoveredCat = null"
                        >
                            <RouterLink
                                :to="{ name: 'category', params: { slug: cat.slug } }"
                                @click="catalogMenuOpen = false; hoveredCat = null"
                                class="flex items-center justify-between gap-2 px-4 py-2.5 hover:bg-gray-50 text-dark hover:text-primary transition-colors text-sm">
                                {{ cat.name }}
                                <svg v-if="cat.children?.length" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </RouterLink>
                            <!-- Flyout подменю подкатегорий -->
                            <div
                                v-if="cat.children?.length && hoveredCat === cat.id"
                                class="absolute left-full top-0 w-56 bg-white shadow-xl rounded-lg border border-gray-100 py-2 z-50"
                            >
                                <RouterLink
                                    v-for="child in cat.children"
                                    :key="child.id"
                                    :to="{ name: 'category', params: { slug: child.slug } }"
                                    @click="catalogMenuOpen = false; hoveredCat = null"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-dark hover:text-primary transition-colors text-sm">
                                    {{ child.name }}
                                </RouterLink>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="handleSearch" class="flex-1">
                    <div class="flex">
                        <input v-model="searchQuery" type="text" placeholder="Поиск программ..."
                            class="flex-1 border border-gray-300 border-r-0 rounded-l-lg px-4 py-2 text-sm focus:outline-none focus:border-primary" />
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-r-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>

                <nav class="hidden lg:flex items-center gap-5 text-sm font-medium">
                    <RouterLink to="/vendors" class="text-dark hover:text-primary transition-colors">Вендоры</RouterLink>
                    <RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }" class="text-dark hover:text-primary transition-colors">Хиты</RouterLink>
                    <RouterLink :to="{ name: 'catalog', query: { is_sale: 1 } }" class="text-accent hover:text-accent-dark transition-colors font-semibold">Акции</RouterLink>
                </nav>
            </div>
        </div>

        <!-- Нижняя полоса: карусель разделов компании -->
        <div v-if="companyPages.length" class="bg-slate-700 border-t border-slate-600">
            <div class="container mx-auto px-2 flex items-center">

                <!-- Стрелка «назад» — появляется когда есть что прокручивать влево -->
                <button
                    v-show="showPrev"
                    @click="scrollNav(-220)"
                    class="flex-shrink-0 w-8 h-9 flex items-center justify-center text-gray-400 hover:text-white transition-colors"
                    aria-label="Прокрутить назад"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!--
                    Скроллируемый контейнер карусели.
                    overflow-x-auto разрешает прокрутку JS-кодом (scrollBy),
                    CSS класс nav-carousel скрывает нативный скроллбар.
                -->
                <div
                    ref="navRef"
                    class="nav-carousel flex-1 overflow-x-auto"
                    @scroll="updateArrows"
                >
                    <nav class="flex items-center min-w-max">
                        <RouterLink
                            v-for="page in companyPages"
                            :key="page.slug"
                            :to="{ name: 'company-page', params: { slug: page.slug } }"
                            class="flex-shrink-0 px-4 py-2.5 text-xs font-medium text-gray-300 hover:text-white hover:bg-slate-600 transition-colors whitespace-nowrap border-b-2 border-transparent hover:border-primary"
                            active-class="text-white bg-slate-600 border-primary"
                        >
                            {{ page.title }}
                        </RouterLink>
                    </nav>
                </div>

                <!-- Стрелка «вперёд» — появляется когда есть что прокручивать вправо -->
                <button
                    v-show="showNext"
                    @click="scrollNav(220)"
                    class="flex-shrink-0 w-8 h-9 flex items-center justify-center text-gray-400 hover:text-white transition-colors"
                    aria-label="Прокрутить вперёд"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

            </div>
        </div>

    </header>

    <div v-if="catalogMenuOpen" @click="catalogMenuOpen = false" class="fixed inset-0 z-40" />
</template>

<style scoped>
/* Скрываем нативный скроллбар карусели во всех браузерах */
.nav-carousel::-webkit-scrollbar {
    display: none;
}
.nav-carousel {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
