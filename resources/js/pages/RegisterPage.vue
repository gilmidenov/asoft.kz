<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

const form    = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const errors  = ref({})
const loading = ref(false)

async function submit() {
    errors.value  = {}
    loading.value = true
    try {
        await authStore.register(form.value.name, form.value.email, form.value.password, form.value.password_confirmation)
        router.push('/account')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
        } else {
            errors.value = { general: [e.response?.data?.message || 'Ошибка регистрации'] }
        }
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
                <h1 class="text-2xl font-bold text-dark">Регистрация</h1>
                <p class="text-muted mt-1">Создайте аккаунт Atlas Software</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Имя</label>
                        <input v-model="form.name" type="text" required placeholder="Ваше имя"
                            class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors"
                            :class="errors.name ? 'border-red-400' : 'border-gray-300'" />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Email</label>
                        <input v-model="form.email" type="email" required placeholder="example@mail.com"
                            class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors"
                            :class="errors.email ? 'border-red-400' : 'border-gray-300'" />
                        <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Телефон <span class="text-muted text-xs">(необязательно)</span></label>
                        <input v-model="form.phone" type="tel" placeholder="+7 (700) 000-00-00"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Пароль</label>
                        <input v-model="form.password" type="password" required placeholder="Минимум 8 символов"
                            class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors"
                            :class="errors.password ? 'border-red-400' : 'border-gray-300'" />
                        <p v-if="errors.password" class="text-red-500 text-xs mt-1">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2">Повторите пароль</label>
                        <input v-model="form.password_confirmation" type="password" required placeholder="••••••••"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors" />
                    </div>

                    <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3">
                        {{ errors.general[0] }}
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-700 disabled:opacity-60 transition-colors">
                        {{ loading ? 'Регистрируем...' : 'Создать аккаунт' }}
                    </button>
                </form>

                <p class="text-center text-sm text-muted mt-6">
                    Уже есть аккаунт?
                    <RouterLink to="/login" class="text-primary font-medium hover:underline">Войдите</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>
