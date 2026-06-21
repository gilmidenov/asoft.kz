<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCatalogStore } from '@/stores/catalog'

const authStore    = useAuthStore()
const cartStore    = useCartStore()
const catalogStore = useCatalogStore()
const router       = useRouter()

const searchQuery     = ref('')
const catalogMenuOpen = ref(false)

onMounted(() => catalogStore.fetchCategories())

function handleSearch() {
    if (searchQuery.value.trim()) {
        router.push({ name: 'catalog', query: { search: searchQuery.value } })
    }
}
</script>

<template>
    <header class="sticky top-0 z-50 shadow-md">

        <!-- Верхняя тёмная полоса -->
        <div class="bg-header text-white">
            <div class="container mx-auto px-4 py-2 flex items-center justify-between text-sm">

                <RouterLink to="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center font-bold text-white text-lg">A</div>
                    <div>
                        <span class="font-bold text-white text-lg tracking-tight">Atlas</span>
                        <span class="text-primary font-bold text-lg"> Software</span>
                    </div>
                </RouterLink>

                <a href="tel:+77001234567" class="text-gray-300 hover:text-white transition-colors">
                    +7 (700) 123-45-67
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

        <!-- Нижняя белая полоса -->
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
                        class="absolute top-full left-0 mt-1 w-64 bg-white shadow-xl rounded-lg border border-gray-100 py-2 z-50">
                        <RouterLink
                            v-for="cat in catalogStore.categories"
                            :key="cat.id"
                            :to="{ name: 'category', params: { slug: cat.slug } }"
                            @click="catalogMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-dark hover:text-primary transition-colors">
                            {{ cat.name }}
                        </RouterLink>
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
    </header>

    <div v-if="catalogMenuOpen" @click="catalogMenuOpen = false" class="fixed inset-0 z-40" />
</template>
