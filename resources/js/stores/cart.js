import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useCartStore = defineStore('cart', () => {
    const items   = ref([])
    const loading = ref(false)

    const count = computed(() => items.value.reduce((s, i) => s + i.quantity, 0))
    const total = computed(() => items.value.reduce((s, i) => s + i.license.price * i.quantity, 0))

    async function fetchCart() {
        loading.value = true
        try {
            const { data } = await axios.get('/cart')
            items.value = data.items
        } finally {
            loading.value = false
        }
    }

    async function addItem(licenseId, quantity = 1) {
        await axios.post('/cart', { product_license_id: licenseId, quantity })
        await fetchCart()
    }

    async function updateItem(itemId, quantity) {
        await axios.patch(`/cart/${itemId}`, { quantity })
        const item = items.value.find(i => i.id === itemId)
        if (item) item.quantity = quantity
    }

    async function removeItem(itemId) {
        await axios.delete(`/cart/${itemId}`)
        items.value = items.value.filter(i => i.id !== itemId)
    }

    async function clearCart() {
        await axios.delete('/cart')
        items.value = []
    }

    return { items, loading, count, total, fetchCart, addItem, updateItem, removeItem, clearCart }
})
