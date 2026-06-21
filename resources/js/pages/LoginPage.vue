<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const route     = useRoute()
const authStore = useAuthStore()

const form    = ref({ email: '', password: '' })
const error   = ref('')
const loading = ref(false)

async function submit() {
    error.value   = ''
    loading.value = true
    try {
        await authStore.login(form.value.email, form.value.password)
        const redirect = route.query.redirect || '/account'
        router.push(redirect)
    } catch (e) {
        error.value = e.response?.data?.message || 'Неверный email или пароль'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-primary rounded-2xl text-white text-2xl font-bold mb-4">A</div>
                <h1 class="text-2xl font-bold text-dark">Вход в аккаунт</h1>
                <p class="text-muted mt-1">Введите данные для входа</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Email</label>
                        <input v-model="form.email" type="email" required placeholder="example@mail.com"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Пароль</label>
                        <input v-model="form.password" type="password" required placeholder="••••••••"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors" />
                    </div>

                    <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3">
                        {{ error }}
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-700 disabled:opacity-60 transition-colors">
                        {{ loading ? 'Входим...' : 'Войти' }}
                    </button>
                </form>

                <p class="text-center text-sm text-muted mt-6">
                    Нет аккаунта?
                    <RouterLink to="/register" class="text-primary font-medium hover:underline">Зарегистрируйтесь</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>
