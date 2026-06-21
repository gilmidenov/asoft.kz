<script setup>
import { onMounted } from 'vue'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()
onMounted(() => catalogStore.fetchVendors())
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-dark mb-6">Вендоры</h1>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <RouterLink
                v-for="vendor in catalogStore.vendors"
                :key="vendor.id"
                :to="{ name: 'catalog', query: { vendor: vendor.slug } }"
                class="bg-white border border-gray-100 rounded-xl p-4 text-center hover:border-primary hover:shadow-sm transition-all"
            >
                <div class="font-medium text-dark text-sm">{{ vendor.name }}</div>
                <div v-if="vendor.country" class="text-xs text-muted mt-1">{{ vendor.country }}</div>
            </RouterLink>
        </div>
    </div>
</template>
