import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useCatalogStore = defineStore('catalog', () => {
    const categories = ref([])
    const vendors    = ref([])

    async function fetchCategories() {
        if (categories.value.length) return
        const { data } = await axios.get('/categories')
        categories.value = data
    }

    async function fetchVendors() {
        if (vendors.value.length) return
        const { data } = await axios.get('/vendors')
        vendors.value = data
    }

    async function refreshCategories() {
        const { data } = await axios.get('/categories')
        categories.value = data
    }

    async function refreshVendors() {
        const { data } = await axios.get('/vendors')
        vendors.value = data
    }

    return { categories, vendors, fetchCategories, fetchVendors, refreshCategories, refreshVendors }
})
