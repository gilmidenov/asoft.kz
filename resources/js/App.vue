<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'

const authStore = useAuthStore()
const cartStore = useCartStore()

onMounted(async () => {
    await authStore.fetchUser()
    await cartStore.fetchCart()
})
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <AppHeader />
        <main class="flex-1">
            <RouterView v-slot="{ Component }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" />
                </Transition>
            </RouterView>
        </main>
        <AppFooter />
    </div>
</template>

<style>
.page-enter-active,
.page-leave-active {
    transition: opacity 0.15s ease;
}
.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>
