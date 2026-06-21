import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const user  = ref(null)
    const token = ref(localStorage.getItem('auth_token'))

    const isAuthenticated = computed(() => !!token.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    function saveSession(data) {
        token.value = data.token
        user.value  = data.user
        localStorage.setItem('auth_token', data.token)
        localStorage.setItem('auth_role', data.user.role ?? 'customer')
    }

    async function login(email, password) {
        const { data } = await axios.post('/auth/login', { email, password })
        saveSession(data)
    }

    async function register(name, email, password, passwordConfirmation) {
        const { data } = await axios.post('/auth/register', {
            name, email, password,
            password_confirmation: passwordConfirmation,
        })
        saveSession(data)
    }

    async function logout() {
        try { await axios.post('/auth/logout') } finally {
            token.value = null
            user.value  = null
            localStorage.removeItem('auth_token')
            localStorage.removeItem('auth_role')
        }
    }

    async function fetchUser() {
        if (!token.value) return
        try {
            const { data } = await axios.get('/auth/me')
            user.value = data
            localStorage.setItem('auth_role', data.role ?? 'customer')
        } catch {
            token.value = null
            user.value  = null
            localStorage.removeItem('auth_token')
            localStorage.removeItem('auth_role')
        }
    }

    return { user, token, isAuthenticated, isAdmin, login, register, logout, fetchUser }
})
