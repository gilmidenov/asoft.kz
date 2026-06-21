<script setup>
const emit = defineEmits(['change'])
const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage:    { type: Number, required: true },
})

function getPages() {
    const pages = []
    const delta = 2
    for (let i = 1; i <= props.lastPage; i++) {
        if (i === 1 || i === props.lastPage || (i >= props.currentPage - delta && i <= props.currentPage + delta)) {
            pages.push(i)
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...')
        }
    }
    return pages
}
</script>

<template>
    <div class="flex items-center justify-center gap-1">
        <button @click="emit('change', currentPage - 1)" :disabled="currentPage === 1"
            class="px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-100 transition-colors">←</button>

        <template v-for="page in getPages()" :key="page">
            <span v-if="page === '...'" class="px-2 text-muted">…</span>
            <button v-else @click="emit('change', page)"
                class="w-9 h-9 rounded-lg text-sm font-medium transition-colors"
                :class="page === currentPage ? 'bg-primary text-white' : 'hover:bg-gray-100 text-dark'">
                {{ page }}
            </button>
        </template>

        <button @click="emit('change', currentPage + 1)" :disabled="currentPage === lastPage"
            class="px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-100 transition-colors">→</button>
    </div>
</template>
