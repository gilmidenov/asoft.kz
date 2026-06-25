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
                :to="{ name: 'vendor', params: { slug: vendor.slug } }"
                class="bg-white border border-gray-100 rounded-xl p-4 text-center hover:border-primary hover:shadow-sm transition-all group"
            >
                <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center mx-auto mb-2 group-hover:bg-primary transition-colors overflow-hidden">
                    <img v-if="vendor.logo" :src="vendor.logo" :alt="vendor.name" class="w-full h-full object-contain" />
                    <span v-else class="text-primary group-hover:text-white font-bold text-lg">{{ vendor.name[0] }}</span>
                </div>
                <div class="font-medium text-dark text-sm">{{ vendor.name }}</div>
                <div v-if="vendor.description" class="text-xs text-muted mt-1 line-clamp-1">{{ vendor.description }}</div>
            </RouterLink>
        </div>
    </div>
</template>
